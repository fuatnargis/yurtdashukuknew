<?php
declare(strict_types=1);

// A separate transaction serializes requests for one session, including with a
// transaction pooler. It never shares the admin action's content transaction.
final class DatabaseSession implements SessionHandlerInterface, SessionUpdateTimestampHandlerInterface {
    private ?PDO $connection=null;
    private function connection(): PDO {return $this->connection??=database_connection();}
    public function open(string $path,string $name): bool {return true;}
    public function close(): bool {if($this->connection?->inTransaction())$this->connection->commit();$this->connection=null;return true;}
    public function read(string $id): string|false {
        $db=$this->connection();if(!$db->inTransaction())$db->beginTransaction();
        $db->prepare("INSERT INTO sessions(id,data,expires_at) VALUES(?,'',?) ON CONFLICT(id) DO NOTHING")->execute([$id,time()+3600]);
        $q=$db->prepare('SELECT data,expires_at FROM sessions WHERE id=? FOR UPDATE');$q->execute([$id]);$row=$q->fetch();
        return $row&&(int)$row['expires_at']>time()?(base64_decode($row['data'],true)?:''):'';
    }
    public function write(string $id,string $data): bool {
        $this->connection()->prepare('INSERT INTO sessions(id,data,expires_at) VALUES(?,?,?) ON CONFLICT(id) DO UPDATE SET data=excluded.data,expires_at=excluded.expires_at')->execute([$id,base64_encode($data),time()+3600]);return true;
    }
    public function destroy(string $id): bool {$this->connection()->prepare('DELETE FROM sessions WHERE id=?')->execute([$id]);return true;}
    public function gc(int $max_lifetime): int|false {$q=$this->connection()->prepare('DELETE FROM sessions WHERE expires_at<?');$q->execute([time()]);return $q->rowCount();}
    public function validateId(string $id): bool {$q=$this->connection()->prepare('SELECT 1 FROM sessions WHERE id=? AND expires_at>?');$q->execute([$id,time()]);return (bool)$q->fetchColumn();}
    public function updateTimestamp(string $id,string $data): bool {$q=$this->connection()->prepare('UPDATE sessions SET expires_at=? WHERE id=?');$q->execute([time()+3600,$id]);return true;}
}
