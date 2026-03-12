<template>
	<view :style="colorStyle">
		<view class='bill-details'>
			<!-- 类型筛选导航 -->
			<view class='nav acea-row'>
				<view
					class='item'
					:class='type == 0 ? "on" : ""'
					@click='changeType(0)'
				>全部</view>
				<view
					class='item'
					:class='type == 1 ? "on" : ""'
					@click='changeType(1)'
				>消费</view>
				<view
					class='item'
					:class='type == 2 ? "on" : ""'
					@click='changeType(2)'
				>储值</view>
				<view
					class='item'
					:class='type == "queue_refund" ? "on" : ""'
					@click='changeType("queue_refund")'
				>公排退款</view>
			</view>

			<!-- 账单列表 -->
			<view class='sign-record'>
				<view class='list' v-for="(item, index) in userBillList" :key="index">
					<view class='item'>
						<view class='data'>{{ item.time }}</view>
						<view class='listn'>
							<view
								class='itemn acea-row row-between-wrapper'
								v-for="(vo, indexn) in item.child"
								:key="indexn"
							>
								<view>
									<view class='name line1'>
										{{ vo.title }}
										<!-- 公排退款标记 -->
										<text
											v-if="vo.type === 'queue_refund'"
											class='queue-refund-tag'
										>公排退款</text>
									</view>
									<view class='time-text'>{{ vo.add_time }}</view>
								</view>
								<view class='num' :class="vo.pm ? 'num-add' : 'num-sub'">
									<text v-if="vo.pm">+{{ vo.number }}</text>
									<text v-else>-{{ vo.number }}</text>
								</view>
							</view>
						</view>
					</view>
				</view>

				<!-- 加载更多 -->
				<view class='loadingicon acea-row row-center-wrapper' v-if="userBillList.length > 0">
					<text class='loading iconfont icon-jiazai' :hidden='loading == false'></text>
					{{ loadTitle }}
				</view>

				<!-- 空状态 -->
				<view class="px-20 mt-20" v-if="userBillList.length == 0">
					<emptyPage title="暂无记录～" src="/statics/images/noOrder.gif"></emptyPage>
				</view>
			</view>
		</view>
		<home></home>
	</view>
</template>

<script>
	import { moneyList } from '@/api/user.js';
	import { toLogin } from '@/libs/login.js';
	import { mapGetters } from 'vuex';
	import emptyPage from '@/components/emptyPage.vue';
	import colors from '@/mixins/color';

	/**
	 * 账单明细页
	 *
	 * 展示用户的账单流水，支持按类型筛选：
	 * - 0: 全部
	 * - 1: 消费
	 * - 2: 储值
	 * - "queue_refund": 公排退款（type=queue_refund 时显示专属标记）
	 *
	 * 列表按日期分组，支持上拉分页加载。
	 *
	 * @module pages/users/user_bill/index
	 */
	export default {
		components: {
			emptyPage,
		},
		mixins: [colors],
		data() {
			return {
				/** @type {string} 底部加载提示文字 */
				loadTitle: '加载更多',
				/** @type {boolean} 是否正在加载中（防止重复请求） */
				loading: false,
				/** @type {boolean} 是否已加载全部数据 */
				loadend: false,
				/** @type {number} 当前页码 */
				page: 1,
				/** @type {number} 每页条数 */
				limit: 15,
				/**
				 * 当前筛选类型
				 * 0=全部 1=消费 2=储值 "queue_refund"=公排退款
				 * @type {number|string}
				 */
				type: 0,
				/** @type {Array<Object>} 按日期分组的账单列表 */
				userBillList: [],
				/** @type {Array<string>} 已加载的日期键列表（用于去重分组） */
				times: [],
			};
		},
		computed: {
			...mapGetters(['isLogin']),
		},
		onShow() {
			uni.removeStorageSync('form_type_cart');
			if (this.isLogin) {
				this.getUserBillList();
			} else {
				toLogin();
			}
		},
		/**
		 * 生命周期函数 — 监听页面加载
		 * @param {Object} options - 页面跳转参数
		 * @param {number|string} [options.type=0] - 初始筛选类型
		 */
		onLoad(options) {
			this.type = options.type || 0;
		},
		/**
		 * 页面上拉触底 — 加载下一页
		 */
		onReachBottom() {
			this.getUserBillList();
		},
		/**
		 * 页面滚动事件 — 广播 scroll 事件供子组件使用
		 */
		onPageScroll() {
			uni.$emit('scroll');
		},
		methods: {
			/**
			 * 获取账单明细列表（分页追加）
			 *
			 * 请求 moneyList 接口，将返回数据按日期分组追加到 userBillList。
			 * 若 loading 或 loadend 为 true 则直接返回，防止重复/越界请求。
			 *
			 * @returns {void}
			 */
			getUserBillList() {
				if (this.loading) return;
				if (this.loadend) return;

				this.loading = true;
				this.loadTitle = '';

				moneyList({ page: this.page, limit: this.limit }, this.type)
					.then(res => {
						const { time: timeKeys, list } = res.data;

						// 按日期键建立分组（跨页去重）
						timeKeys.forEach(key => {
							if (!this.times.includes(key)) {
								this.times.push(key);
								this.userBillList.push({ time: key, child: [] });
							}
						});

						// 将明细条目归入对应日期分组
						this.times.forEach((key, idx) => {
							list.forEach(item => {
								if (item.time_key === key) {
									this.userBillList[idx].child.push(item);
								}
							});
						});

						const loadend = list.length < this.limit;
						this.loadend = loadend;
						this.loadTitle = loadend ? '没有更多内容啦~' : '加载更多';
						this.page += 1;
						this.loading = false;
					})
					.catch(() => {
						this.loading = false;
						this.loadTitle = '加载更多';
					});
			},

			/**
			 * 切换账单筛选类型并重置列表
			 *
			 * @param {number|string} type - 目标类型（0全部 1消费 2储值 "queue_refund"公排退款）
			 * @returns {void}
			 */
			changeType(type) {
				if (this.type === type) return;
				this.type = type;
				this.loadend = false;
				this.page = 1;
				this.times = [];
				this.$set(this, 'userBillList', []);
				this.getUserBillList();
			},
		},
	};
</script>

<style scoped lang='scss'>
	.sign-record .list .item .data {
		color: #999;
		padding: 20rpx 30rpx 10rpx;
		font-size: 26rpx;
	}

	.sign-record .list .item .listn {
		width: 710rpx;
		margin: 0 auto;
		border-radius: 24rpx;
	}

	.sign-record .list .item .listn .itemn {
		padding: 0;
		margin: 0 30rpx;
		height: 150rpx;
	}

	.sign-record .list .item .listn .itemn:nth-last-child(1) {
		border-bottom: 0;
	}

	.sign-record .list .item .listn .itemn .name {
		color: #333;
		font-size: 28rpx;
	}

	.sign-record .list .item .listn .itemn .time-text {
		color: #999;
		font-size: 24rpx;
		margin-top: 8rpx;
	}

	.sign-record .list .item .listn .itemn .num {
		font-family: 'Regular';
		font-size: 30rpx;
	}

	.num-add {
		color: var(--view-theme);
	}

	.num-sub {
		color: #333333;
	}

	/* 公排退款标记 */
	.queue-refund-tag {
		display: inline-block;
		margin-left: 10rpx;
		padding: 2rpx 12rpx;
		border-radius: 20rpx;
		font-size: 20rpx;
		color: #fff;
		background: var(--view-theme);
		vertical-align: middle;
		line-height: 1.6;
	}

	/* 顶部类型筛选导航 */
	.bill-details .nav {
		background-color: #fff;
		height: 80rpx;
		width: 100%;
		line-height: 80rpx;
	}

	.bill-details .nav .item {
		flex: 1;
		text-align: center;
		font-size: 28rpx;
		color: #333;
	}

	.bill-details .nav .item.on {
		color: var(--view-theme);
		font-size: 30rpx;
		position: relative;
	}

	.bill-details .nav .item.on::after {
		position: absolute;
		width: 64rpx;
		height: 6rpx;
		border-radius: 20px;
		content: ' ';
		background: var(--view-theme);
		bottom: 0;
		left: 50%;
		margin-left: -32rpx;
	}
</style>
