<template>
  <!-- P17 积分明细页 -->
  <view class="hjf-points-detail-page">

    <!-- Tab 筛选栏 -->
    <view class="tab-nav acea-row">
      <view
        v-for="(tab, idx) in tabs"
        :key="idx"
        class="tab-nav__item"
        :class="{ on: activeTab === idx }"
        @tap="changeTab(idx)"
      >{{ tab.label }}</view>
    </view>

    <!-- 积分流水列表（按日期分组） -->
    <view class="points-list">
      <view
        v-for="(group, gIdx) in list"
        :key="gIdx"
        class="points-list__group"
      >
        <!-- 日期分组标题 -->
        <view class="points-list__date">{{ group.date }}</view>

        <!-- 分组内条目 -->
        <view class="points-list__card">
          <view
            v-for="(item, iIdx) in group.children"
            :key="iIdx"
            class="points-list__item acea-row row-between-wrapper"
          >
            <!-- 左侧：标题 + 时间 -->
            <view class="points-list__info">
              <view class="points-list__title line1">{{ item.title }}</view>
              <view class="points-list__meta acea-row">
                <view class="points-list__time">{{ item.add_time }}</view>
                <view
                  class="points-list__tag"
                  :class="item.status === 'frozen' ? 'tag--frozen' : 'tag--released'"
                >{{ item.status === 'frozen' ? '待释放' : '已释放' }}</view>
              </view>
            </view>

            <!-- 右侧：积分增减 -->
            <view
              class="points-list__points"
              :class="item.pm === 1 ? 'points--add' : 'points--sub'"
            >
              {{ item.pm === 1 ? '+' : '-' }}{{ item.points }}
            </view>
          </view>
        </view>
      </view>

      <!-- 加载更多提示 -->
      <view v-if="list.length > 0" class="loadingicon acea-row row-center-wrapper">
        <text
          v-if="loading"
          class="loading iconfont icon-jiazai"
        ></text>
        <text class="load-title">{{ loadTitle }}</text>
      </view>

      <!-- 空状态 -->
      <view v-if="!loading && list.length === 0" class="empty-wrap">
        <emptyPage title="暂无积分记录～" src="/statics/images/noOrder.gif"></emptyPage>
      </view>
    </view>

  </view>
</template>

<script>
import { getPointsDetail } from '@/api/hjfAssets.js';
import emptyPage from '@/components/emptyPage.vue';
import colors from '@/mixins/color';

/**
 * P17 积分明细页
 *
 * 展示当前用户的积分流水，支持按类型 Tab 筛选（全部/待释放/已释放），
 * 并以日期分组方式分页渲染列表，上拉触底自动加载下一页。
 *
 * @module pages/assets/points_detail
 */
export default {
  name: 'PointsDetail',

  components: { emptyPage },

  mixins: [colors],

  data() {
    return {
      /**
       * Tab 配置：label 展示文案，type 对应 API 参数（'' 表示全部）
       * @type {Array<{ label: string, type: string }>}
       */
      tabs: [
        { label: '全部',     type: '' },
        { label: '待释放',   type: 'frozen' },
        { label: '已释放',   type: 'released' },
      ],

      /** 当前选中 Tab 索引，0=全部 1=待释放 2=已释放 @type {number} */
      activeTab: 0,

      /**
       * 按日期分组后的列表，每项形如 { date: string, children: Array }
       * @type {Array<{ date: string, children: Array<Object> }>}
       */
      list: [],

      /** 已记录的日期键，用于去重分组 @type {string[]} */
      dateKeys: [],

      /** 当前请求页码 @type {number} */
      page: 1,

      /** 每页条数 @type {number} */
      limit: 15,

      /** 是否正在加载（防重入锁） @type {boolean} */
      loading: false,

      /** 是否已加载全部数据 @type {boolean} */
      finished: false,

      /** 底部加载提示文案 @type {string} */
      loadTitle: '加载更多',
    };
  },

  /**
   * 页面加载：支持从资产总览通过 type 参数直接定位 Tab。
   * @param {Object} options - 页面跳转参数
   * @param {string} [options.type] - 可选，'frozen' | 'released'
   */
  onLoad(options) {
    if (options && options.type) {
      const idx = this.tabs.findIndex(t => t.type === options.type);
      if (idx > -1) this.activeTab = idx;
    }
    this.loadList();
  },

  /**
   * 上拉触底：加载下一页数据。
   */
  onReachBottom() {
    this.loadList();
  },

  methods: {
    /**
     * 切换 Tab 筛选，重置分页后重新加载列表。
     * @param {number} idx - 点击的 Tab 索引
     */
    changeTab(idx) {
      if (idx === this.activeTab) return;
      this.activeTab = idx;
      this.resetAndLoad();
    },

    /**
     * 重置所有分页/列表状态，然后发起首页请求。
     */
    resetAndLoad() {
      this.page = 1;
      this.finished = false;
      this.loading = false;
      this.loadTitle = '加载更多';
      this.dateKeys = [];
      this.$set(this, 'list', []);
      this.loadList();
    },

    /**
     * 加载积分明细数据（分页追加），通过 `getPointsDetail` 获取。
     * 按 `add_time` 日期前缀分组追加到 `list`。
     * 防重入：loading 为 true 或 finished 时直接返回。
     */
    loadList() {
      if (this.loading || this.finished) return;
      this.loading = true;
      this.loadTitle = '';

      const currentType = this.tabs[this.activeTab].type;

      const params = {
        page: this.page,
        limit: this.limit,
      };
      if (currentType) params.type = currentType;

      getPointsDetail(params).then(res => {
        const items = (res.data && res.data.list) ? res.data.list : [];

        items.forEach(item => {
          // 取 add_time 的日期部分作为分组 key（格式 "YYYY-MM-DD"）
          const dateKey = (item.add_time || '').split(' ')[0] || '未知日期';
          if (!this.dateKeys.includes(dateKey)) {
            this.dateKeys.push(dateKey);
            this.list.push({ date: dateKey, children: [] });
          }
          const group = this.list.find(g => g.date === dateKey);
          if (group) group.children.push(item);
        });

        const loadend = items.length < this.limit;
        this.finished = loadend;
        this.loadTitle = loadend ? '没有更多内容啦~' : '加载更多';
        if (!loadend) this.page += 1;
        this.loading = false;
      }).catch(() => {
        this.loading = false;
        this.loadTitle = '加载更多';
      });
    },
  },
};
</script>

<style scoped lang="scss">
.hjf-points-detail-page {
  min-height: 100vh;
  background-color: #f5f5f5;
}

/* -------- Tab 导航 -------- */
.tab-nav {
  background-color: #fff;
  height: 88rpx;
  line-height: 88rpx;
  position: sticky;
  top: 0;
  z-index: 10;
  border-bottom: 1rpx solid #f0f0f0;

  &__item {
    flex: 1;
    text-align: center;
    font-size: 28rpx;
    color: #666;
    position: relative;
    transition: color 0.2s;

    &.on {
      color: var(--view-theme);
      font-size: 30rpx;
      font-weight: 500;

      &::after {
        position: absolute;
        content: '';
        width: 48rpx;
        height: 6rpx;
        border-radius: 10rpx;
        background: var(--view-theme);
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
      }
    }
  }
}

/* -------- 积分列表 -------- */
.points-list {
  padding: 20rpx 24rpx;

  &__group {
    margin-bottom: 20rpx;
  }

  &__date {
    font-size: 24rpx;
    color: #999;
    padding: 12rpx 0 10rpx;
  }

  &__card {
    background: #fff;
    border-radius: 16rpx;
    overflow: hidden;
    box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.04);
  }

  &__item {
    padding: 28rpx 28rpx;
    border-bottom: 1rpx solid #f5f5f5;

    &:last-child {
      border-bottom: none;
    }
  }

  &__info {
    flex: 1;
    overflow: hidden;
    padding-right: 20rpx;
  }

  &__title {
    font-size: 28rpx;
    color: #333;
    margin-bottom: 10rpx;
  }

  &__meta {
    align-items: center;
    gap: 12rpx;
  }

  &__time {
    font-size: 22rpx;
    color: #aaa;
  }

  &__tag {
    font-size: 20rpx;
    padding: 2rpx 10rpx;
    border-radius: 20rpx;

    &.tag--frozen {
      color: #ff8c00;
      background: rgba(255, 140, 0, 0.1);
    }

    &.tag--released {
      color: #52c41a;
      background: rgba(82, 196, 26, 0.1);
    }
  }

  &__points {
    font-size: 32rpx;
    font-weight: 600;
    white-space: nowrap;

    &.points--add {
      color: var(--view-theme);
    }

    &.points--sub {
      color: #333;
    }
  }
}

/* -------- 加载更多 & 空状态 -------- */
.loadingicon {
  padding: 24rpx 0;
  font-size: 24rpx;
  color: #aaa;

  .loading {
    margin-right: 8rpx;
    animation: rotating 1.5s linear infinite;
  }

  .load-title {
    font-size: 24rpx;
  }
}

@keyframes rotating {
  from { transform: rotate(0deg); }
  to   { transform: rotate(360deg); }
}

.empty-wrap {
  padding: 60rpx 0;
}
</style>
