<?php

namespace app\models;

use yii\db\Connection;

final class GuestReview
{
    public static function record(string $messageId, int|string $reviewerId, string $decision, ?string $note): ?bool
    {
        $table = GuestMessage::tableName();

        return GuestMessage::getDb()->transaction(static function (Connection $db) use (
            $table, $messageId, $reviewerId, $decision, $note
        ): ?bool {
            // Compare against the current state under the same lock used for the write.
            $status = $db->createCommand("SELECT [[status]] FROM $table WHERE [[id]] = :id FOR UPDATE",
                [':id' => $messageId])->queryScalar();
            if ($status === false) {
                return null;
            }
            if ($status === $decision) {
                return false;
            }

            $now = gmdate('Y-m-d H:i:s');
            $db->createCommand()->insert('{{%guest_review}}', [
                'message_id' => $messageId,
                'reviewer_id' => $reviewerId,
                'decision' => $decision,
                'note' => $note,
                'created_at' => $now,
                'updated_at' => $now,
            ])->execute();
            $db->createCommand()->update($table, ['status' => $decision, 'updated_at' => $now],
                ['id' => $messageId])->execute();

            return true;
        });
    }
}
