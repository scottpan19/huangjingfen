-- ============================================================
-- 黄精粉健康商城 HJF 数据库迁移脚本
-- 版本：Phase 2
-- 日期：2026-03-15
-- 执行说明：
--   1. 确保 MySQL 8.0+，数据库前缀为 eb_
--   2. 按顺序执行 P2-01 ~ P2-05
--   3. 所有操作均使用 IF NOT EXISTS / IGNORE，可幂等重复执行
-- ============================================================

-- ============================================================
-- P2-01: 公排池表
-- ============================================================

CREATE TABLE IF NOT EXISTS `eb_queue_pool` (
  `id`            int(11)        NOT NULL AUTO_INCREMENT              COMMENT '自增主键',
  `uid`           int(11)        NOT NULL DEFAULT 0                   COMMENT '用户 ID',
  `order_id`      varchar(50)    NOT NULL DEFAULT ''                  COMMENT '来源订单号（eb_store_order.order_id）',
  `amount`        decimal(10,2)  NOT NULL DEFAULT 3600.00             COMMENT '报单金额（元）',
  `queue_no`      int(11)        NOT NULL DEFAULT 0                   COMMENT '全局排队序号（自增，唯一）',
  `status`        tinyint(1)     NOT NULL DEFAULT 0                   COMMENT '状态：0=排队中 1=已退款',
  `refund_time`   int(11)        NOT NULL DEFAULT 0                   COMMENT '退款时间（Unix 时间戳）',
  `trigger_batch` int(11)        NOT NULL DEFAULT 0                   COMMENT '触发退款的批次号',
  `add_time`      int(11)        NOT NULL DEFAULT 0                   COMMENT '入队时间（Unix 时间戳）',
  PRIMARY KEY (`id`),
  UNIQUE  KEY `uniq_queue_no`         (`queue_no`),
  INDEX         `idx_uid`             (`uid`),
  INDEX         `idx_status_add_time` (`status`, `add_time`),
  INDEX         `idx_trigger_batch`   (`trigger_batch`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='公排池';


-- ============================================================
-- P2-02: 积分释放日志表
-- ============================================================

CREATE TABLE IF NOT EXISTS `eb_points_release_log` (
  `id`           int(11)      NOT NULL AUTO_INCREMENT             COMMENT '自增主键',
  `uid`          int(11)      NOT NULL DEFAULT 0                  COMMENT '用户 ID',
  `points`       int(11)      NOT NULL DEFAULT 0                  COMMENT '积分数量（绝对值）',
  `pm`           tinyint(1)   NOT NULL DEFAULT 1                  COMMENT '收支方向：1=收入 0=支出',
  `type`         varchar(50)  NOT NULL DEFAULT ''                 COMMENT '类型：reward_direct/reward_umbrella/release/consume',
  `title`        varchar(255) NOT NULL DEFAULT ''                 COMMENT '标题',
  `mark`         varchar(500) NOT NULL DEFAULT ''                 COMMENT '备注',
  `status`       varchar(30)  NOT NULL DEFAULT 'frozen'           COMMENT '状态：frozen=冻结 released=已释放 consumed=已消费',
  `order_id`     varchar(50)  NOT NULL DEFAULT ''                 COMMENT '关联订单号（奖励来源），释放记录为空',
  `release_date` date                  DEFAULT NULL               COMMENT '释放日期（每日释放时填写）',
  `add_time`     int(11)      NOT NULL DEFAULT 0                  COMMENT '记录时间（Unix 时间戳）',
  PRIMARY KEY (`id`),
  INDEX `idx_uid_type`     (`uid`, `type`),
  INDEX `idx_uid_add_time` (`uid`, `add_time`),
  INDEX `idx_release_date` (`release_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='积分释放明细日志';


-- ============================================================
-- P2-03: eb_user 扩展字段
-- ============================================================

ALTER TABLE `eb_user`
  ADD COLUMN IF NOT EXISTS `member_level`      tinyint(1)  NOT NULL DEFAULT 0 COMMENT '会员等级：0普通 1创客 2云店 3服务商 4分公司',
  ADD COLUMN IF NOT EXISTS `no_assess`         tinyint(1)  NOT NULL DEFAULT 0 COMMENT '不计入伞下业绩：1=不计入',
  ADD COLUMN IF NOT EXISTS `frozen_points`     int(11)     NOT NULL DEFAULT 0 COMMENT '待释放（冻结）积分',
  ADD COLUMN IF NOT EXISTS `available_points`  int(11)     NOT NULL DEFAULT 0 COMMENT '可用积分';

-- 为 member_level 建索引（用于 Admin 列表筛选）
ALTER TABLE `eb_user`
  ADD INDEX IF NOT EXISTS `idx_member_level` (`member_level`);


-- ============================================================
-- P2-04: eb_store_product 扩展字段
-- ============================================================

ALTER TABLE `eb_store_product`
  ADD COLUMN IF NOT EXISTS `is_queue_goods`  tinyint(1)   NOT NULL DEFAULT 0  COMMENT '是否报单商品：1=是',
  ADD COLUMN IF NOT EXISTS `allow_pay_types` varchar(255) NOT NULL DEFAULT ''  COMMENT '允许积分支付类型（JSON数组）';

-- 为商品列表公排筛选建索引
ALTER TABLE `eb_store_product`
  ADD INDEX IF NOT EXISTS `idx_is_queue_goods` (`is_queue_goods`);

-- 同步 is_queue_goods 到订单表（后续新订单由代码写入，存量数据可按需更新）
ALTER TABLE `eb_store_order`
  ADD COLUMN IF NOT EXISTS `is_queue_goods` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否报单商品订单：1=是';

ALTER TABLE `eb_store_order`
  ADD INDEX IF NOT EXISTS `idx_is_queue_goods` (`is_queue_goods`);


-- ============================================================
-- P2-05: eb_system_config 初始化配置项
--
-- 字段说明（与 CRMEB 原表保持一致）：
--   menu_name   = 配置键名（代码中 SystemConfigService::get() 读取）
--   value       = 默认值（字符串）
--   info        = 后台显示名称
--   desc        = 说明文字
--   config_tab_id = 0（不归属某分组，便于独立管理）
--   status      = 1（启用）
-- ============================================================

-- 防止重复执行报错，使用 INSERT IGNORE
INSERT IGNORE INTO `eb_system_config`
  (`is_store`, `menu_name`, `type`, `input_type`, `config_tab_id`,
   `parameter`, `upload_type`, `required`, `width`, `high`,
   `value`, `info`, `desc`, `sort`, `status`)
VALUES

-- 公排触发倍数：每入 N 单退款第1单（默认 4）
(0, 'hjf_trigger_multiple', 'text', 'input', 0,
 '', 0, '', 100, 0,
 '4', '公排触发倍数', '每进入N单公排触发退款第1单，默认4', 10, 1),

-- 积分每日释放比例（‰，默认 4，即 4‰）
(0, 'hjf_release_rate', 'text', 'input', 0,
 '', 0, '', 100, 0,
 '4', '积分每日释放比例(‰)', '每日释放：frozen_points × N / 1000，默认4（即4‰）', 20, 1),

-- 提现手续费率（%，默认 7，即 7%）
(0, 'hjf_fee_rate', 'text', 'input', 0,
 '', 0, '', 100, 0,
 '7', '提现手续费率(%)', '申请提现时收取的手续费比例，默认7%', 30, 1),

-- 等级升级门槛：普通→创客（直推N单）
(0, 'hjf_level_direct_require_1', 'text', 'input', 0,
 '', 0, '', 100, 0,
 '3', '创客升级所需直推单数', '普通会员直推N单报单商品后升级为创客，默认3', 40, 1),

-- 等级升级门槛：创客→云店（伞下N单）
(0, 'hjf_level_umbrella_require_2', 'text', 'input', 0,
 '', 0, '', 100, 0,
 '30', '云店升级所需伞下单数', '创客伞下业绩达到N单后升级为云店，默认30', 50, 1),

-- 等级升级门槛：云店→服务商（伞下N单）
(0, 'hjf_level_umbrella_require_3', 'text', 'input', 0,
 '', 0, '', 100, 0,
 '100', '服务商升级所需伞下单数', '云店伞下业绩达到N单后升级为服务商，默认100', 60, 1),

-- 等级升级门槛：服务商→分公司（伞下N单）
(0, 'hjf_level_umbrella_require_4', 'text', 'input', 0,
 '', 0, '', 100, 0,
 '1000', '分公司升级所需伞下单数', '服务商伞下业绩达到N单后升级为分公司，默认1000', 70, 1),

-- 直推奖励积分：创客直推可得N积分
(0, 'hjf_reward_direct_1', 'text', 'input', 0,
 '', 0, '', 100, 0,
 '500', '创客直推奖励积分', '创客等级直推一单报单商品可获得的冻结积分，默认500', 80, 1),

-- 直推奖励积分：云店
(0, 'hjf_reward_direct_2', 'text', 'input', 0,
 '', 0, '', 100, 0,
 '800', '云店直推奖励积分', '云店等级直推一单报单商品可获得的冻结积分，默认800', 90, 1),

-- 直推奖励积分：服务商
(0, 'hjf_reward_direct_3', 'text', 'input', 0,
 '', 0, '', 100, 0,
 '1000', '服务商直推奖励积分', '服务商等级直推一单报单商品可获得的冻结积分，默认1000', 100, 1),

-- 直推奖励积分：分公司
(0, 'hjf_reward_direct_4', 'text', 'input', 0,
 '', 0, '', 100, 0,
 '1300', '分公司直推奖励积分', '分公司等级直推一单报单商品可获得的冻结积分，默认1300', 110, 1),

-- 伞下奖励积分：创客（无伞下奖励）
(0, 'hjf_reward_umbrella_1', 'text', 'input', 0,
 '', 0, '', 100, 0,
 '0', '创客伞下奖励积分', '创客等级伞下奖励积分（级差），默认0（无伞下奖励）', 120, 1),

-- 伞下奖励积分：云店
(0, 'hjf_reward_umbrella_2', 'text', 'input', 0,
 '', 0, '', 100, 0,
 '300', '云店伞下奖励积分', '云店等级伞下奖励积分（级差），默认300', 130, 1),

-- 伞下奖励积分：服务商
(0, 'hjf_reward_umbrella_3', 'text', 'input', 0,
 '', 0, '', 100, 0,
 '200', '服务商伞下奖励积分', '服务商等级伞下奖励积分（级差），默认200', 140, 1),

-- 伞下奖励积分：分公司
(0, 'hjf_reward_umbrella_4', 'text', 'input', 0,
 '', 0, '', 100, 0,
 '300', '分公司伞下奖励积分', '分公司等级伞下奖励积分（级差），默认300', 150, 1);


-- ============================================================
-- 迁移完成校验（可手动执行检查）
-- ============================================================

-- SELECT TABLE_NAME FROM information_schema.TABLES
--   WHERE TABLE_SCHEMA = DATABASE()
--   AND TABLE_NAME IN ('eb_queue_pool', 'eb_points_release_log');

-- SELECT COLUMN_NAME FROM information_schema.COLUMNS
--   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'eb_user'
--   AND COLUMN_NAME IN ('member_level','no_assess','frozen_points','available_points');

-- SELECT COLUMN_NAME FROM information_schema.COLUMNS
--   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'eb_store_product'
--   AND COLUMN_NAME IN ('is_queue_goods','allow_pay_types');

-- SELECT menu_name, value FROM eb_system_config
--   WHERE menu_name LIKE 'hjf_%' ORDER BY sort;
