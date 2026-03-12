<template>
  <view class="hjf-member-badge" :class="sizeClass">
    <view class="badge-icon" :style="iconStyle">
      <text class="icon-text">{{ levelText }}</text>
    </view>
    <text class="badge-name">{{ displayName }}</text>
  </view>
</template>

<script>
/**
 * 会员等级颜色映射（0-4 对应文档 3.2.4）
 * @constant
 * @type {Object<number, string>}
 */
const LEVEL_COLORS = {
  0: '#999999', // 普通
  1: '#CD7F32', // 创客
  2: '#C0C0C0', // 云店
  3: '#FFD700', // 服务商
  4: '#8B5CF6'  // 分公司
};

/**
 * 等级默认名称（level 无 levelName 时回退）
 * @constant
 * @type {string[]}
 */
const LEVEL_NAMES = ['普通', '创客', '云店', '服务商', '分公司'];

/**
 * HjfMemberBadge — 会员等级徽章组件
 *
 * 展示会员等级图标（圆形数字徽）+ 等级名称，支持三种尺寸与五档等级颜色。
 * 参考：docs/frontend-new-pages-spec.md 第 3.2.4 节。
 *
 * @component HjfMemberBadge
 * @example
 * <HjfMemberBadge :level="2" levelName="云店" size="normal" />
 */
export default {
  name: 'HjfMemberBadge',
  props: {
    /**
     * 会员等级数字 (0-4)
     * 0: 普通, 1: 创客, 2: 云店, 3: 服务商, 4: 分公司
     * @type {number}
     * @default 0
     */
    level: {
      type: Number,
      default: 0,
      validator(val) {
        return val >= 0 && val <= 4;
      }
    },
    /**
     * 等级名称展示文案（可选，不传则按 level 回退为默认名称）
     * @type {string}
     * @default ''
     */
    levelName: {
      type: String,
      default: ''
    },
    /**
     * 尺寸：'small' | 'normal' | 'large'
     * @type {'small'|'normal'|'large'}
     * @default 'normal'
     */
    size: {
      type: String,
      default: 'normal',
      validator(val) {
        return ['small', 'normal', 'large'].indexOf(val) !== -1;
      }
    }
  },
  computed: {
    /** 尺寸类名，用于 .size-small / .size-normal / .size-large */
    sizeClass() {
      return `size-${this.size}`;
    },
    /** 当前等级对应的主题色 */
    levelColor() {
      const key = Math.min(4, Math.max(0, this.level));
      return LEVEL_COLORS[key] || LEVEL_COLORS[0];
    },
    /** 徽章图标内联样式（背景色、边框色） */
    iconStyle() {
      return {
        backgroundColor: this.levelColor,
        borderColor: this.levelColor
      };
    },
    /** 最终展示的等级名称（优先 levelName，否则 LEVEL_NAMES[level]） */
    displayName() {
      if (this.levelName && this.levelName.trim()) {
        return this.levelName.trim();
      }
      const key = Math.min(4, Math.max(0, this.level));
      return LEVEL_NAMES[key] || LEVEL_NAMES[0];
    },
    /** 徽章内显示的等级数字文案 */
    levelText() {
      const key = Math.min(4, Math.max(0, this.level));
      return String(key);
    }
  }
};
</script>

<style scoped lang="scss">
.hjf-member-badge {
  display: inline-flex;
  align-items: center;
  flex-wrap: nowrap;

  .badge-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    border: 2rpx solid;
    flex-shrink: 0;

    .icon-text {
      color: #fff;
      font-weight: bold;
      text-shadow: 0 1rpx 2rpx rgba(0, 0, 0, 0.2);
    }
  }

  .badge-name {
    margin-left: 8rpx;
    font-weight: 500;
    white-space: nowrap;
  }

  &.size-small {
    .badge-icon {
      width: 32rpx;
      height: 32rpx;

      .icon-text {
        font-size: 20rpx;
      }
    }

    .badge-name {
      font-size: 22rpx;
    }
  }

  &.size-normal {
    .badge-icon {
      width: 40rpx;
      height: 40rpx;

      .icon-text {
        font-size: 24rpx;
      }
    }

    .badge-name {
      font-size: 26rpx;
    }
  }

  &.size-large {
    .badge-icon {
      width: 52rpx;
      height: 52rpx;

      .icon-text {
        font-size: 30rpx;
      }
    }

    .badge-name {
      font-size: 30rpx;
    }
  }
}
</style>
