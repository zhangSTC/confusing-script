CREATE TABLE `wangwen_book`
(
    `id`         int unsigned     NOT NULL AUTO_INCREMENT COMMENT '自增id',
    `name`       varchar(50)      NOT NULL DEFAULT '' COMMENT '书名',
    `xclass`     varchar(50)      NOT NULL DEFAULT '' COMMENT '类型',
    `author`     varchar(50)      NOT NULL DEFAULT '' COMMENT '作者',
    `intro`      varchar(600)     NOT NULL DEFAULT '' COMMENT '简介',
    `status`     tinyint unsigned NOT NULL DEFAULT 0 COMMENT '状态: 1-连载中, 2-已完结',
    `chapter`    int unsigned     NOT NULL DEFAULT 0 COMMENT '章节总数',
    `uri`        varchar(100)     NOT NULL DEFAULT '' COMMENT '网页uri',
    `created_at` timestamp        NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` timestamp        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci COMMENT ='网文目录表';

CREATE TABLE `wangwen_chapter`
(
    `id`         int unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
    `book_id`    int unsigned NOT NULL DEFAULT 0 COMMENT '书id',
    `seq`        int unsigned NOT NULL DEFAULT 0 COMMENT '章节编号',
    `title`      varchar(100) NOT NULL DEFAULT '' COMMENT '标题',
    `uri`        varchar(50)  NOT NULL DEFAULT '' COMMENT '网页uri',
    `sync_at`    int unsigned NOT NULL DEFAULT 0 COMMENT '最新同步时间戳',
    `created_at` timestamp    NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at` timestamp    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_bid_seq` (`book_id`, `seq`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci COMMENT ='网文章节表';
