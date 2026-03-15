<template>
  <view :style="colorStyle" class="queue-rules">

    <!-- 公排机制图示 -->
    <view class="section">
      <view class="section-title">公排机制图示</view>
      <view class="mechanism-card">
        <view class="mechanism-title">进四退一流程</view>
        <view class="flow-diagram">
          <!-- 第一步 -->
          <view class="flow-step">
            <view class="step-circle step-in">进</view>
            <view class="step-desc">用户A 购买报单商品</view>
            <view class="step-sub">¥3,600 → 自动入队（#1）</view>
          </view>
          <view class="flow-arrow">↓</view>
          <!-- 第二步 -->
          <view class="flow-step">
            <view class="step-circle step-in">进</view>
            <view class="step-desc">用户B 购买报单商品</view>
            <view class="step-sub">¥3,600 → 自动入队（#2）</view>
          </view>
          <view class="flow-arrow">↓</view>
          <!-- 第三步 -->
          <view class="flow-step">
            <view class="step-circle step-in">进</view>
            <view class="step-desc">用户C 购买报单商品</view>
            <view class="step-sub">¥3,600 → 自动入队（#3）</view>
          </view>
          <view class="flow-arrow">↓</view>
          <!-- 第四步触发退款 -->
          <view class="flow-step trigger">
            <view class="step-circle step-in">进</view>
            <view class="step-desc">用户D 购买报单商品</view>
            <view class="step-sub">¥3,600 → 自动入队（#4）</view>
            <view class="trigger-badge">触发退款！</view>
          </view>
          <view class="flow-arrow refund-arrow">↓</view>
          <!-- 退款 -->
          <view class="flow-step refund-step">
            <view class="step-circle step-out">退</view>
            <view class="step-desc">用户A 全额退款</view>
            <view class="step-sub">¥3,600 → 返还至现金余额</view>
          </view>
        </view>

        <!-- 循环说明 -->
        <view class="cycle-note">
          <text class="cycle-text">🔄 如此循环：每进入4单，最早的1单全额退款</text>
        </view>
      </view>

      <!-- 示例计算卡片 -->
      <view class="example-card">
        <view class="example-title">示例计算</view>
        <view class="example-row">
          <text class="example-label">报单金额</text>
          <text class="example-value">¥3,600.00</text>
        </view>
        <view class="example-row">
          <text class="example-label">退款金额</text>
          <text class="example-value highlight">¥3,600.00（全额）</text>
        </view>
        <view class="example-row">
          <text class="example-label">平均等待单数</text>
          <text class="example-value">约 3 单（即约3倍报单量）</text>
        </view>
        <view class="example-divider"></view>
        <view class="example-desc">
          假设公排池每天新增20单，您的排队序号为第14位，预计约需等待 3 天可触发退款。
          实际等待时间取决于整体报单速度，报单越活跃退款越快。
        </view>
      </view>
    </view>

    <!-- 规则条款列表 -->
    <view class="section">
      <view class="section-title">规则条款</view>
      <view class="rules-card">
        <view
          class="rule-item"
          v-for="(rule, index) in ruleItems"
          :key="index"
        >
          <view class="rule-index">{{ index + 1 }}</view>
          <view class="rule-content">
            <view class="rule-title-text">{{ rule.title }}</view>
            <view class="rule-body">{{ rule.content }}</view>
          </view>
        </view>
      </view>
    </view>

    <!-- 常见问题 FAQ 手风琴 -->
    <view class="section">
      <view class="section-title">常见问题</view>
      <view class="faq-list">
        <view
          class="faq-item"
          v-for="(item, index) in faqItems"
          :key="index"
          @click="toggleFaq(index)"
        >
          <view class="faq-header acea-row row-between-wrapper">
            <view class="faq-question">{{ item.question }}</view>
            <view class="faq-arrow" :class="{ 'arrow-up': activeIndex === index }">›</view>
          </view>
          <view class="faq-answer" v-if="activeIndex === index">{{ item.answer }}</view>
        </view>
      </view>
    </view>

    <!-- 底部声明 -->
    <view class="footer-note">
      <text>本平台公排规则最终解释权归黄精粉健康商城所有</text>
    </view>

  </view>
</template>

<script>
/**
 * @file pages/queue/rules.vue
 * @description P14 公排规则说明页 — 静态展示页面，无 API 调用
 *
 * 页面结构：
 *  1. 公排机制图示（进四退一流程图 + 示例计算）
 *  2. 规则条款列表（编号条款）
 *  3. 常见问题 FAQ 手风琴（activeIndex 控制展开/收起）
 *
 * @module pages/queue/rules
 * @requires mixins/color  — 提供 colorStyle 计算属性（主题色 CSS 变量注入）
 */
import colors from '@/mixins/color';

export default {
  name: 'QueueRules',

  mixins: [colors],

  data() {
    return {
      /**
       * 当前展开的 FAQ 索引，-1 表示全部折叠
       * @type {number}
       */
      activeIndex: -1,

      /**
       * @type {Array<{title: string, content: string}>}
       * @description 规则条款列表，静态数据
       */
      ruleItems: [
        {
          title: '全额退款保障',
          content: '参与公排的报单金额为 ¥3,600，退款时按原金额全额返还至您的现金余额，不收取任何手续费。'
        },
        {
          title: '进四退一机制',
          content: '公排池按照入队时间先后排队。每当新入队单数达到当前最早排队单数的4倍时，最早的1单自动触发退款。'
        },
        {
          title: '退款自动到账',
          content: '退款金额将在触发退款后立即到账至您的现金余额，无需手动申请，可在"我的资产"中查看。'
        },
        {
          title: '入队资格',
          content: '购买报单商品（黄精粉套餐 ¥3,600）后，系统自动为该订单分配全局唯一的排队序号，按购买时间先后排序。'
        },
        {
          title: '多单独立排队',
          content: '同一用户购买多单报单商品，每单独立分配排队序号，各自独立参与公排循环，互不影响。'
        },
        {
          title: '积分奖励叠加',
          content: '公排退款与积分奖励体系相互独立，参与公排的同时可正常获得推荐积分奖励，两者并行不悖。'
        },
        {
          title: '规则变更通知',
          content: '若平台对公排规则进行调整，将提前通过公告及消息通知用户，变更后的规则不溯及既往订单。'
        }
      ],

      /**
       * @type {Array<{question: string, answer: string}>}
       * @description 常见问题列表
       */
      faqItems: [
        {
          question: '参与公排后多久能收到退款？',
          answer: '等待时间取决于公排池的整体报单速度。每进入4单触发最早1单退款。若每天新增约20单，一般约3-5天可收到退款。您可在"公排状态"页查看实时进度和预估等待时间。'
        },
        {
          question: '退款会到哪里？',
          answer: '退款金额将全额返还至您在平台的现金余额，可在"我的资产"中查看，并可随时申请提现（提现手续费7%）。'
        },
        {
          question: '一个人可以参与多次公排吗？',
          answer: '可以。每次购买报单商品均会独立进入公排队列，获得新的排队序号。多单独立排队，各自触发退款，相互不影响。'
        },
        {
          question: '公排退款后还能继续参与吗？',
          answer: '可以。退款到账后您可以再次购买报单商品重新入队，循环享受公排返利。'
        },
        {
          question: '为什么我的排队序号不是第1位？',
          answer: '公排池是全平台共享队列，您的排队序号代表全局位置。序号前面的用户将优先触发退款，请耐心等待。'
        },
        {
          question: '公排和积分奖励可以同时获得吗？',
          answer: '可以。购买报单商品后，您的直接推荐人可获得积分奖励，同时该订单进入公排队列。两套机制并行运作，互不影响。'
        },
        {
          question: '如何查看我的排队进度？',
          answer: '进入"公排状态"页可查看：您的排队序号、当前批次进度（X/4）、预计等待时间。页面实时展示全局公排进度。'
        }
      ]
    };
  },

  methods: {
    /**
     * 切换 FAQ 手风琴展开/收起
     * @param {number} index - FAQ 条目索引
     */
    toggleFaq(index) {
      this.activeIndex = this.activeIndex === index ? -1 : index;
    }
  }
};
</script>

<style scoped lang="scss">
.queue-rules {
  min-height: 100vh;
  background-color: #f5f5f5;
  padding-bottom: 40rpx;
}

/* ===== 通用区块 ===== */
.section {
  margin: 20rpx 24rpx 0;
}

.section-title {
  font-size: 30rpx;
  font-weight: 600;
  color: #282828;
  padding: 24rpx 0 16rpx;
}

/* ===== 机制图示卡片 ===== */
.mechanism-card {
  background: #fff;
  border-radius: 16rpx;
  padding: 30rpx 28rpx;
  box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.06);
}

.mechanism-title {
  font-size: 28rpx;
  font-weight: 600;
  color: var(--view-theme);
  text-align: center;
  margin-bottom: 30rpx;
}

/* 流程图 */
.flow-diagram {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.flow-step {
  width: 100%;
  background: #f9f9f9;
  border-radius: 12rpx;
  padding: 18rpx 24rpx;
  text-align: center;
  position: relative;

  &.trigger {
    background: #fff7f0;
    border: 2rpx solid #ff9d4d;
  }

  &.refund-step {
    background: #f0fff4;
    border: 2rpx solid #52c41a;
  }
}

.step-circle {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 52rpx;
  height: 52rpx;
  border-radius: 50%;
  font-size: 24rpx;
  font-weight: 700;
  color: #fff;
  margin-bottom: 10rpx;

  &.step-in {
    background: var(--view-theme);
  }

  &.step-out {
    background: #52c41a;
  }
}

.step-desc {
  font-size: 26rpx;
  color: #333;
  font-weight: 500;
}

.step-sub {
  font-size: 22rpx;
  color: #999;
  margin-top: 6rpx;
}

.trigger-badge {
  display: inline-block;
  background: #ff9d4d;
  color: #fff;
  font-size: 20rpx;
  border-radius: 20rpx;
  padding: 4rpx 16rpx;
  margin-top: 10rpx;
}

.flow-arrow {
  font-size: 36rpx;
  color: #ccc;
  line-height: 50rpx;
  text-align: center;

  &.refund-arrow {
    color: #52c41a;
  }
}

/* 循环说明 */
.cycle-note {
  display: flex;
  align-items: center;
  justify-content: center;
  margin-top: 24rpx;
  padding: 16rpx 20rpx;
  background: #f0f4ff;
  border-radius: 10rpx;
}

.cycle-text {
  font-size: 24rpx;
  color: #555;
}

/* 示例计算卡片 */
.example-card {
  background: #fff;
  border-radius: 16rpx;
  padding: 28rpx;
  margin-top: 20rpx;
  box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.06);
}

.example-title {
  font-size: 26rpx;
  font-weight: 600;
  color: #282828;
  margin-bottom: 18rpx;
}

.example-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10rpx 0;
}

.example-label {
  font-size: 26rpx;
  color: #666;
}

.example-value {
  font-size: 26rpx;
  color: #333;
  font-weight: 500;

  &.highlight {
    color: #52c41a;
    font-weight: 700;
  }
}

.example-divider {
  height: 1rpx;
  background: #f0f0f0;
  margin: 16rpx 0;
}

.example-desc {
  font-size: 24rpx;
  color: #999;
  line-height: 1.8;
}

/* ===== 规则条款 ===== */
.rules-card {
  background: #fff;
  border-radius: 16rpx;
  padding: 10rpx 28rpx;
  box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.06);
}

.rule-item {
  display: flex;
  padding: 24rpx 0;
  border-bottom: 1rpx solid #f5f5f5;

  &:last-child {
    border-bottom: none;
  }
}

.rule-index {
  flex-shrink: 0;
  width: 44rpx;
  height: 44rpx;
  border-radius: 50%;
  background: var(--view-theme);
  color: #fff;
  font-size: 22rpx;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 20rpx;
  margin-top: 4rpx;
}

.rule-content {
  flex: 1;
}

.rule-title-text {
  font-size: 28rpx;
  font-weight: 600;
  color: #333;
  margin-bottom: 8rpx;
}

.rule-body {
  font-size: 24rpx;
  color: #777;
  line-height: 1.7;
}

/* ===== FAQ 手风琴 ===== */
.faq-list {
  background: #fff;
  border-radius: 16rpx;
  overflow: hidden;
  box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.06);
}

.faq-item {
  border-bottom: 1rpx solid #f5f5f5;

  &:last-child {
    border-bottom: none;
  }
}

.faq-header {
  padding: 28rpx;
  align-items: center;
}

.faq-question {
  flex: 1;
  font-size: 28rpx;
  color: #333;
  font-weight: 500;
  line-height: 1.5;
  padding-right: 20rpx;
}

.faq-arrow {
  flex-shrink: 0;
  font-size: 40rpx;
  color: #ccc;
  transform: rotate(90deg);
  transition: transform 0.25s ease;
  line-height: 1;

  &.arrow-up {
    transform: rotate(-90deg);
    color: var(--view-theme);
  }
}

.faq-answer {
  padding: 0 28rpx 24rpx;
  font-size: 24rpx;
  color: #888;
  line-height: 1.8;
  background: #fafafa;
}

/* ===== 底部声明 ===== */
.footer-note {
  margin: 30rpx 24rpx 0;
  text-align: center;
  font-size: 22rpx;
  color: #bbb;
}
</style>
