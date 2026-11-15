<?php
/**
 * 用途：创建比赛表 fixture（W07 比赛与赛果浏览），含状态、比分与来源约束。
 * 作者：孙锘梵（2410868，成员3）
 */

use yii\db\Migration;

/**
 * 比赛：比赛状态与发布状态是两条独立维度；比分只在完赛时成对存在。
 */
class m261115_100004_create_fixture_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%fixture}}', [
            'id' => $this->primaryKey()->unsigned(),
            'sample_key' => $this->string(80)->notNull()->comment('稳定导入标识：official-2026-* / demo-*'),
            'tournament_id' => $this->integer()->unsigned()->notNull()->comment('FK -> tournament.id'),
            'venue_id' => $this->integer()->unsigned()->notNull()->comment('FK -> venue.id'),
            'home_team_id' => $this->integer()->unsigned()->notNull()->comment('FK -> national_team.id'),
            'away_team_id' => $this->integer()->unsigned()->notNull()->comment('FK -> national_team.id，不得等于主队'),
            'stage' => $this->string(32)->notNull()->defaultValue('group')->comment('本期仅 group'),
            'group_name' => $this->string(8)->null()->comment('如 A，演示可空'),
            'kickoff_at' => $this->dateTime()->notNull()->comment('UTC 开球时间，秒精度'),
            'match_status' => $this->string(16)->notNull()->defaultValue('scheduled')->comment('scheduled / finished'),
            'publication_status' => $this->string(16)->notNull()->defaultValue('draft')->comment('draft / published'),
            'home_score' => $this->smallInteger()->unsigned()->null()->comment('0-99，未开赛为 NULL'),
            'away_score' => $this->smallInteger()->unsigned()->null()->comment('0-99，未开赛为 NULL'),
            'is_demo' => $this->smallInteger(1)->unsigned()->notNull()->defaultValue(1)->comment('0 / 1，新建默认演示'),
            'source_url' => $this->string(2048)->null()->comment('正式样本必填 FIFA 来源，演示为空'),
            'source_checked_at' => $this->dateTime()->null()->comment('正式样本核对时间，UTC'),
            'published_at' => $this->dateTime()->null()->comment('首次发布 UTC 时间，草稿为空'),
            'score_updated_at' => $this->dateTime()->null()->comment('完赛比分录入/变更时间，未开赛为空'),
            'created_at' => $this->dateTime()->notNull()->comment('UTC'),
            'updated_at' => $this->dateTime()->notNull()->comment('UTC'),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->createIndex('uq_fixture_sample_key', '{{%fixture}}', 'sample_key', true);
        $this->createIndex('idx_fixture_public_status_time', '{{%fixture}}', ['publication_status', 'match_status', 'kickoff_at', 'id']);
        $this->createIndex('idx_fixture_result_update', '{{%fixture}}', ['publication_status', 'match_status', 'score_updated_at', 'id']);
        $this->createIndex('idx_fixture_tournament', '{{%fixture}}', 'tournament_id');
        $this->createIndex('idx_fixture_venue', '{{%fixture}}', 'venue_id');
        $this->createIndex('idx_fixture_home', '{{%fixture}}', 'home_team_id');
        $this->createIndex('idx_fixture_away', '{{%fixture}}', 'away_team_id');

        $this->addForeignKey('fk_fixture_tournament', '{{%fixture}}', 'tournament_id', '{{%tournament}}', 'id', 'RESTRICT', 'RESTRICT');
        $this->addForeignKey('fk_fixture_venue', '{{%fixture}}', 'venue_id', '{{%venue}}', 'id', 'RESTRICT', 'RESTRICT');
        $this->addForeignKey('fk_fixture_home_team', '{{%fixture}}', 'home_team_id', '{{%national_team}}', 'id', 'RESTRICT', 'RESTRICT');
        $this->addForeignKey('fk_fixture_away_team', '{{%fixture}}', 'away_team_id', '{{%national_team}}', 'id', 'RESTRICT', 'RESTRICT');

        // 数据库层兜底校验；应用层仍在模型中做同样的校验，不依赖 MySQL 版本。
        $this->execute("ALTER TABLE {{%fixture}} ADD CONSTRAINT chk_fixture_match_status CHECK (match_status IN ('scheduled','finished'))");
        $this->execute("ALTER TABLE {{%fixture}} ADD CONSTRAINT chk_fixture_publication_status CHECK (publication_status IN ('draft','published'))");
        $this->execute("ALTER TABLE {{%fixture}} ADD CONSTRAINT chk_fixture_is_demo CHECK (is_demo IN (0,1))");
        $this->execute("ALTER TABLE {{%fixture}} ADD CONSTRAINT chk_fixture_teams_differ CHECK (home_team_id <> away_team_id)");
        $this->execute("ALTER TABLE {{%fixture}} ADD CONSTRAINT chk_fixture_score_pair CHECK ((home_score IS NULL AND away_score IS NULL) OR (home_score BETWEEN 0 AND 99 AND away_score BETWEEN 0 AND 99))");
        $this->execute("ALTER TABLE {{%fixture}} ADD CONSTRAINT chk_fixture_status_score CHECK ((match_status = 'scheduled' AND home_score IS NULL AND away_score IS NULL AND score_updated_at IS NULL) OR (match_status = 'finished' AND home_score IS NOT NULL AND away_score IS NOT NULL AND score_updated_at IS NOT NULL))");
        $this->execute("ALTER TABLE {{%fixture}} ADD CONSTRAINT chk_fixture_published_at CHECK ((publication_status = 'draft' AND published_at IS NULL) OR (publication_status = 'published' AND published_at IS NOT NULL))");
        $this->execute("ALTER TABLE {{%fixture}} ADD CONSTRAINT chk_fixture_source CHECK ((is_demo = 1) OR (is_demo = 0 AND source_url IS NOT NULL AND source_checked_at IS NOT NULL))");
    }

    public function safeDown()
    {
        $this->dropTable('{{%fixture}}');
    }
}
