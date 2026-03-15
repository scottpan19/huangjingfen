<template>
	<view :style="colorStyle">
		<view class='commission-details'>
			<!-- 团队收益统计卡片（5.1.7） -->
			<view class="team-stats-card">
				<view class="team-stats-title">团队业绩统计</view>
				<view class="team-stats-row">
					<view class="team-stats-item">
						<view class="team-stats-num">{{ teamData.direct_count }}</view>
						<view class="team-stats-label">直推人数</view>
					</view>
					<view class="team-stats-divider"></view>
					<view class="team-stats-item">
						<view class="team-stats-num">{{ teamData.umbrella_count }}</view>
						<view class="team-stats-label">伞下人数</view>
					</view>
					<view class="team-stats-divider"></view>
					<view class="team-stats-item">
						<view class="team-stats-num text-primary">{{ teamData.umbrella_orders }}</view>
						<view class="team-stats-label">团队订单数</view>
					</view>
				</view>
			</view>

			<view class='search acea-row row-between-wrapper' v-if="recordType != 1 && recordType != 4">
				<view class='input'>
					<text class="iconfont icon-ic_search"></text>
					<input
						placeholder='搜索用户名称'
						placeholder-class='placeholder'
						v-model="keyword"
						@confirm="submitForm"
						confirm-type='search'
						name="search"
					></input>
				</view>
			</view>

			<timeSlot @changeTime="changeTime"></timeSlot>

			<view class='sign-record'>
				<view class="top_num" v-if="recordType != 4 && recordList.length">
					支出：{{expend || 0}} 积分 &nbsp;&nbsp;&nbsp; 收入：{{income || 0}} 积分
				</view>
				<view class="box">
					<block v-for="(item, index) in recordList" :key="index" v-if="recordList.length > 0">
						<view class='list'>
							<view class='item'>
								<view class='listn'>
									<view class='itemn1 flex justify-between'>
										<view>
											<view class='name line1'>
												{{item.title}}
												<text class="status_badge default" v-if="recordType == 4 && item.status == 0">待审核</text>
												<text class="status_badge error" v-if="recordType == 4 && item.status == 2">审核未通过</text>
											</view>
											<!-- 积分来源标签（直推 / 伞下） -->
											<view class="income-type-tag" v-if="item.type">
												<text class="tag-direct" v-if="item.type === 'direct'">直推奖励</text>
												<text class="tag-umbrella" v-else-if="item.type === 'umbrella'">伞下奖励</text>
											</view>
											<view class="mark" v-if="item.extract_status == -1">原因：{{item.extract_msg}}</view>
											<view>{{item.add_time}}</view>
											<view v-if="item.is_frozen && item.is_frozen == 1">佣金冻结中，解冻时间：{{item.frozen_time}}</view>
										</view>
										<view>
											<!-- 积分数量展示（不带 ¥，见 6.1.5 §2） -->
											<view class='num' :class="recordType == 4 && item.status == 0 ? 'on' : ''"
												v-if="item.pm == 1">+{{item.points !== undefined ? item.points : item.number}}</view>
											<view class='num' v-else>-{{item.points !== undefined ? item.points : item.number}}</view>
											<view class="fail" v-if="item.extract_status == -1 && item.type == 'extract'">审核未通过</view>
											<view class="wait" v-if="item.extract_status == 0 && item.type == 'extract'">待审核</view>
											<view class="wait" v-if="item.is_frozen == 1">冻结中</view>
											<view class="w-154 h-56 rd-30rpx flex-center mt-16 bg-color fs-24 text--w111-fff"
												v-if="item.extract_status == 0 && item.type == 'extract'"
												@tap="extractCancel(item.link_id)">取消提现</view>
											<!-- #ifdef MP-WEIXIN -->
											<view class="w-154 h-56 rd-30rpx flex-center mt-16 ml-12 bg-color fs-24 text--w111-fff"
												v-if="item.wechat_state == 1 && item.type == 'extract'"
												@tap="jumpPath('/pages/users/user_spread_money/receiving?type=1&id=' + item.extract_order_id)">立即收款</view>
											<!-- #endif -->
											<!-- #ifdef H5 -->
											<view class="w-154 h-56 rd-30rpx flex-center mt-16 ml-12 bg-color fs-24 text--w111-fff"
												v-if="item.wechat_state == 1 && item.type == 'extract' && isWeixin"
												@tap="jumpPath('/pages/users/user_spread_money/receiving?type=1&id=' + item.extract_order_id)">立即收款</view>
											<!-- #endif -->
										</view>
									</view>
								</view>
							</view>
						</view>
					</block>
				</view>

				<view class='loadingicon acea-row row-center-wrapper' v-if="recordList.length">
					<text class='loading iconfont icon-jiazai' :hidden='loading == false'></text>{{loadTitle}}
				</view>
				<view class="empty" v-if="!recordList.length">
					<emptyPage title='暂无数据~' src="/statics/images/noOrder.gif"></emptyPage>
				</view>
			</view>
		</view>
		<home></home>
	</view>
</template>

<script>
	import { moneyList, getSpreadInfo, spreadCount, extractCancelApi } from '@/api/user.js';
	import { getTeamData, getTeamIncome } from '@/api/hjfMember.js';
	import { toLogin } from '@/libs/login.js';
	import { mapGetters } from 'vuex';
	import emptyPage from '@/components/emptyPage.vue';
	import colors from '@/mixins/color.js';
	import timeSlot from '../components/timeSlot/index.vue';
	// #ifdef H5
	import Auth from '@/libs/wechat';
	// #endif

	export default {
		components: { emptyPage, timeSlot },
		mixins: [colors],

		data() {
			return {
				name: '',
				keyword: '',
				type: 0,
				page: 1,
				limit: 15,
				loading: false,
				loadend: false,
				loadTitle: '加载更多',
				recordList: [],
				recordType: 0,
				recordCount: 0,
				extractCount: 0,
				times: [],
				start: 0,
				stop: 0,
				income: '',
				expend: '',
				disabled: false,

				/**
				 * 团队概况数据
				 * @type {{ direct_count: number, umbrella_count: number, umbrella_orders: number }}
				 */
				teamData: {
					direct_count: 0,
					umbrella_count: 0,
					umbrella_orders: 0,
				},

				/**
				 * 团队收益明细列表（来自 getTeamIncome）
				 * @type {Array<{ id: number, title: string, type: string, points: number, from_nickname: string, add_time: string }>}
				 */
				teamIncome: [],

				// #ifdef H5
				isWeixin: Auth.isWeixin(),
				// #endif
			};
		},

		computed: {
			...mapGetters(['isLogin']),
		},

		onLoad(options) {
			if (this.isLogin) {
				this.type = options.type;
			} else {
				toLogin();
			}
		},

		onShow() {
			uni.removeStorageSync('form_type_cart');
			this.page = 1;
			this.limit = 20;
			this.loadend = false;
			this.loading = false;
			this.status = false;
			this.$set(this, 'recordList', []);
			this.$set(this, 'times', []);

			this.loadTeamData();

			const type = this.type;
			if (type == 1) {
				uni.setNavigationBarTitle({ title: '推荐收益' });
				this.name = '提现总额';
				this.recordType = 3;
				this.getRecordList();
			} else if (type == 2) {
				uni.setNavigationBarTitle({ title: '推荐收益' });
				this.name = '推荐收益明细';
				this.recordType = 3;
				this.getRecordList();
			} else if (type == 4) {
				uni.setNavigationBarTitle({ title: '提现记录' });
				this.name = '提现明细';
				this.recordType = 4;
				this.getRecordList();
			} else {
				uni.showToast({
					title: '参数错误',
					icon: 'none',
					duration: 1000,
					mask: true,
					success() {
						setTimeout(() => {
							// #ifndef H5
							uni.navigateBack({ delta: 1 });
							// #endif
							// #ifdef H5
							history.back();
							// #endif
						}, 1200);
					},
				});
			}
		},

		methods: {
			/**
			 * 加载团队概况和收益明细，统一在 onShow 调用
			 * @returns {void}
			 */
			loadTeamData() {
				getTeamData().then(res => {
					const d = (res && res.data) || {};
					this.teamData = {
						direct_count: d.direct_count || 0,
						umbrella_count: d.umbrella_count || 0,
						umbrella_orders: d.umbrella_orders || 0,
					};
				}).catch(() => {});

				getTeamIncome({ page: 1, limit: this.limit }).then(res => {
					this.teamIncome = (res && res.data && res.data.list) || [];
				}).catch(() => {});
			},

			/**
			 * 搜索框确认时重置并重新加载列表
			 * @returns {void}
			 */
			submitForm() {
				this.page = 1;
				this.limit = 20;
				this.loadend = false;
				this.loading = false;
				this.status = false;
				this.$set(this, 'recordList', []);
				this.$set(this, 'times', []);
				this.getRecordList();
			},

			/**
			 * 时间筛选回调
			 * @param {{ start: number, stop: number }} time - 筛选时间段
			 * @returns {void}
			 */
			changeTime(time) {
				this.start = time.start;
				this.stop = time.stop;
				this.page = 1;
				this.loadend = false;
				this.$set(this, 'recordList', []);
				this.getRecordList();
			},

			/**
			 * 加载收益流水列表（分页追加）
			 * @returns {void}
			 */
			getRecordList() {
				const that = this;
				const { page, limit, recordType } = that;
				if (that.loading || that.loadend) return;
				that.loading = true;
				that.loadTitle = '';
				moneyList({
					keyword: this.keyword,
					start: this.start,
					stop: this.stop,
					page,
					limit,
				}, recordType).then(res => {
					this.expend = res.data.expend;
					this.income = res.data.income;
					this.recordList = this.recordList.concat(res.data.list);
					const loadend = res.data.list.length < that.limit;
					that.loadend = loadend;
					that.loadTitle = loadend ? '没有更多内容啦~' : '加载更多';
					that.page += 1;
					that.loading = false;
				}).catch(() => {
					that.loading = false;
					that.loadTitle = '加载更多';
				});
			},

			/**
			 * 页面内跳转
			 * @param {string} url - 目标路由
			 * @returns {void}
			 */
			jumpPath(url) {
				uni.navigateTo({ url });
			},

			/**
			 * 取消提现申请
			 * @param {number|string} id - 提现订单 ID
			 * @returns {void}
			 */
			extractCancel(id) {
				if (this.disabled) return;
				this.disabled = true;
				extractCancelApi(id).then(res => {
					this.disabled = false;
					this.changeTime({ start: 0, stop: 0 });
					return this.$util.Tips({ title: res.msg });
				}).catch(err => {
					this.disabled = false;
					return this.$util.Tips({ title: err });
				});
			},
		},

		onReachBottom() {
			this.getRecordList();
		},
	};
</script>

<style scoped lang="scss">
	.empty {
		margin: 0 20rpx 20rpx 20rpx;
	}

	/* ===== 团队统计卡片 ===== */
	.team-stats-card {
		background: linear-gradient(135deg, #4e9f3d 0%, #1e5128 100%);
		border-radius: 20rpx;
		margin: 20rpx 20rpx 0;
		padding: 30rpx 20rpx 24rpx;
		color: #fff;
	}

	.team-stats-title {
		font-size: 28rpx;
		font-weight: 600;
		margin-bottom: 24rpx;
		opacity: 0.92;
	}

	.team-stats-row {
		display: flex;
		align-items: center;
		justify-content: space-around;
	}

	.team-stats-item {
		flex: 1;
		text-align: center;
	}

	.team-stats-num {
		font-size: 40rpx;
		font-weight: bold;
		line-height: 1.2;
	}

	.team-stats-num.text-primary {
		color: #ffe082;
	}

	.team-stats-label {
		font-size: 24rpx;
		opacity: 0.8;
		margin-top: 8rpx;
	}

	.team-stats-divider {
		width: 1rpx;
		height: 56rpx;
		background: rgba(255, 255, 255, 0.3);
	}

	/* ===== 积分来源标签 ===== */
	.income-type-tag {
		margin-bottom: 8rpx;
	}

	.tag-direct,
	.tag-umbrella {
		display: inline-block;
		height: 36rpx;
		border-radius: 6rpx;
		font-size: 22rpx;
		line-height: 36rpx;
		padding: 0 10rpx;
	}

	.tag-direct {
		background: rgba(78, 159, 61, 0.12);
		color: #4e9f3d;
	}

	.tag-umbrella {
		background: rgba(24, 144, 255, 0.1);
		color: #1890ff;
	}

	/* ===== 搜索框 ===== */
	.commission-details .search {
		width: 100%;
		background-color: #fff;
		padding: 24rpx 20rpx;
		box-sizing: border-box;
	}

	.commission-details .search .input {
		width: 100%;
		height: 72rpx;
		border-radius: 50rpx;
		background-color: #f5f5f5;
		position: relative;
	}

	.commission-details .search .input input {
		height: 100%;
		font-size: 26rpx;
		padding-left: 70rpx;
	}

	.commission-details .search .input .placeholder {
		color: #bbb;
	}

	.commission-details .search .input .iconfont {
		position: absolute;
		left: 28rpx;
		color: #999;
		font-size: 28rpx;
		top: 50%;
		transform: translateY(-50%);
	}

	/* ===== 列表区 ===== */
	.sign-record {
		margin-top: 20rpx;
	}

	.box {
		border-radius: 24rpx;
		margin: 0 20rpx;
		overflow: hidden;
	}

	.top_num {
		padding: 10rpx 30rpx 30rpx 30rpx;
		font-size: 24rpx;
		color: #999;
	}

	.radius15 {
		border-radius: 14rpx 14rpx 0 0;
	}

	.sign-record .list .item .listn .itemn1 {
		border-bottom: 1rpx solid #eee;
		padding: 22rpx 24rpx;
	}

	.sign-record .list .item .listn .itemn1 .name {
		width: 390rpx;
		font-size: 28rpx;
		color: #333;
		margin-bottom: 12rpx;
	}

	.sign-record .list .item .listn .itemn1 .num {
		font-size: 36rpx;
		color: #333333;
		font-family: 'Regular';
		text-align: right;
	}

	.sign-record .list .item .listn .itemn1 .num.font-color {
		color: #e93323 !important;
	}

	.sign-record .list .item .listn .itemn1 .fail {
		color: #E93323;
		margin-top: 14rpx;
		text-align: right;
	}

	.sign-record .list .item .listn .itemn1 .wait {
		color: #FFB200;
		margin-top: 14rpx;
		text-align: right;
	}

	.mark {
		margin-bottom: 10rpx;
	}

	.status_badge {
		display: inline-block;
		height: 40rpx;
		border-radius: 8rpx;
		font-size: 24rpx;
		line-height: 40rpx;
		font-family: PingFangSC-Regular, PingFang SC;
		font-weight: 400;
		margin-left: 16rpx;
		padding: 0 12rpx 0;
	}

	.success {
		background: rgba(24, 144, 255, .1);
		color: #1890FF;
	}

	.default {
		background: #FFF1E5;
		color: #FF7D00;
	}

	.error {
		background: #FDEBEB;
		color: #F53F3F;
	}
</style>
