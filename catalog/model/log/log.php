<?php

class ModelLogLog extends Model
{
    public function log($messages, $xtra_tags)
    {
        $time = \Carbon\Carbon::now()->toIso8601String();
        $statement = $this->pdo->prepare("INSERT INTO sl_synclist_logs(log, time, type) VALUES(?,?,?)");
        $statement->bindParam(1, $messages, PDO::PARAM_STR);
        $statement->bindParam(2, $time);
        $statement->bindParam(3, $xtra_tags);
        $statement->execute();
    }
}
