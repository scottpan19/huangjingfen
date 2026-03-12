<template>
  <span class="hjf-member-badge" :class="sizeClass">
    <span class="badge-icon" :style="iconStyle">{{ levelText }}</span>
    <span class="badge-name" :style="nameStyle">{{ displayName }}</span>
  </span>
</template>

<script>
/**
 * 会员等级颜色映射（0-4 对应 HJF 业务等级）
 */
const LEVEL_COLORS = {
  0: '#999999',
  1: '#CD7F32',
  2: '#C0C0C0',
  3: '#FFD700',
  4: '#8B5CF6'
};

const LEVEL_NAMES = ['普通会员', '创客', '云店', '服务商', '分公司'];

/**
 * HjfMemberBadge (Admin Web 版) — 会员等级徽章组件
 *
 * @example
 * <HjfMemberBadge :level="2" size="small" />
 * <HjfMemberBadge :level="3" levelName="服务商" size="normal" />
 */
export default {
  name: 'HjfMemberBadge',
  props: {
    /** 会员等级 0-4 */
    level: {
      type: Number,
      default: 0,
      validator: (val) => val >= 0 && val <= 4
    },
    /** 等级名称（可选，不传则按 level 回退默认名称） */
    levelName: {
      type: String,
      default: ''
    },
    /** 尺寸：'small' | 'normal' | 'large' */
    size: {
      type: String,
      default: 'small',
      validator: (val) => ['small', 'normal', 'large'].includes(val)
    }
  },
  computed: {
    sizeClass() {
      return `size-${this.size}`;
    },
    levelColor() {
      const key = Math.min(4, Math.max(0, this.level));
      return LEVEL_COLORS[key] || LEVEL_COLORS[0];
    },
    iconStyle() {
      return {
        backgroundColor: this.levelColor,
        borderColor: this.levelColor
      };
    },
    nameStyle() {
      return { color: this.levelColor };
    },
    displayName() {
      if (this.levelName && this.levelName.trim()) return this.levelName.trim();
      const key = Math.min(4, Math.max(0, this.level));
      return LEVEL_NAMES[key] || LEVEL_NAMES[0];
    },
    levelText() {
      return String(Math.min(4, Math.max(0, this.level)));
    }
  }
};
</script>

<style scoped>
.hjf-member-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  white-space: nowrap;
}

.badge-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  color: #fff;
  font-weight: bold;
  flex-shrink: 0;
  line-height: 1;
}

.badge-name {
  font-weight: 500;
}

/* small */
.size-small .badge-icon {
  width: 16px;
  height: 16px;
  font-size: 10px;
}
.size-small .badge-name {
  font-size: 12px;
}

/* normal */
.size-normal .badge-icon {
  width: 20px;
  height: 20px;
  font-size: 12px;
}
.size-normal .badge-name {
  font-size: 13px;
}

/* large */
.size-large .badge-icon {
  width: 26px;
  height: 26px;
  font-size: 14px;
}
.size-large .badge-name {
  font-size: 14px;
}
</style>
