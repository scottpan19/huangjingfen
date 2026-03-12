<template>
	<view v-if="showPanel" class="hjf-demo-container">
		<!-- 悬浮按钮 -->
		<view v-if="!expanded" class="demo-fab" @tap="togglePanel">
			<text class="iconfont icon-shezhi fs-44 text-white"></text>
		</view>
		
		<!-- 展开的控制面板 -->
		<view v-if="expanded" class="demo-panel">
			<view class="panel-header">
				<text class="panel-title">演示控制面板</text>
				<text class="iconfont icon-ic_close fs-40" @tap="togglePanel"></text>
			</view>
			
			<view class="panel-body">
				<!-- 场景切换 -->
				<view class="section">
					<view class="section-title">当前场景：{{ scenarioName }}</view>
					<view class="scenario-btns">
						<view 
							v-for="s in scenarios" 
							:key="s.id" 
							:class="['scenario-btn', currentScenario === s.id ? 'active' : '']"
							@tap="switchScenario(s.id)"
						>
							<text class="scenario-label">{{ s.name }}</text>
							<text class="scenario-desc">{{ s.desc }}</text>
						</view>
					</view>
				</view>
				
				<!-- 特殊操作 -->
				<view class="section">
					<view class="section-title">特殊操作</view>
					<view class="action-btns">
						<view class="action-btn" @tap="triggerRefundNotice">
							<text class="iconfont icon-qianbao fs-36"></text>
							<text class="action-label">退款弹窗</text>
						</view>
						<view class="action-btn" @tap="clearGuideFlag">
							<text class="iconfont icon-shuaxin fs-36"></text>
							<text class="action-label">重置引导</text>
						</view>
					</view>
				</view>
				
				<!-- 快捷跳转 -->
				<view class="section">
					<view class="section-title">快捷跳转</view>
					<scroll-view scroll-y class="nav-scroll">
						<view 
							v-for="nav in quickNavs" 
							:key="nav.path"
							class="nav-item"
							@tap="navigateTo(nav.path)"
						>
							<text class="nav-label">{{ nav.label }}</text>
							<text class="iconfont icon-ic_rightarrow fs-28"></text>
						</view>
					</scroll-view>
				</view>
			</view>
		</view>
		
		<!-- 遮罩层 -->
		<view v-if="expanded" class="demo-mask" @tap="togglePanel"></view>
	</view>
</template>

<script>
import { setMockScenario, getCurrentScenario } from '@/utils/hjfMockData.js';

export default {
	name: 'HjfDemoPanel',
	data() {
		return {
			showPanel: false,
			expanded: false,
			currentScenario: 'B',
			scenarios: [
				{
					id: 'A',
					name: '场景 A',
					desc: '新用户首次体验'
				},
				{
					id: 'B',
					name: '场景 B',
					desc: '活跃用户等待退款'
				},
				{
					id: 'C',
					name: '场景 C',
					desc: 'VIP用户刚退款'
				}
			],
			quickNavs: [
				{ label: 'P23 新用户引导', path: '/pages/guide/hjf_intro' },
				{ label: 'P01 首页', path: '/pages/index/index' },
				{ label: 'P04 个人中心', path: '/pages/user/index' },
				{ label: 'P12 公排状态', path: '/pages/queue/status' },
				{ label: 'P13 公排历史', path: '/pages/queue/history' },
				{ label: 'P14 公排规则', path: '/pages/queue/rules' },
				{ label: 'P15 我的资产', path: '/pages/assets/index' },
				{ label: 'P18 积分明细', path: '/pages/assets/points_detail' },
				{ label: 'P16 提现页', path: '/pages/users/user_cash/index' },
				{ label: 'P19 推荐收益', path: '/pages/users/user_spread_money/index' }
			]
		};
	},
	computed: {
		scenarioName() {
			const scenario = this.scenarios.find(s => s.id === this.currentScenario);
			return scenario ? `${scenario.name} - ${scenario.desc}` : '';
		}
	},
	mounted() {
		// 仅在开发环境显示
		// #ifdef H5
		this.showPanel = process.env.NODE_ENV !== 'production';
		// #endif
		// #ifndef H5
		this.showPanel = true; // 小程序默认显示，用于演示
		// #endif
		
		// 获取当前场景
		this.currentScenario = getCurrentScenario();
		
		console.log('[HjfDemoPanel] 演示控制面板已加载，当前场景:', this.currentScenario);
	},
	methods: {
		togglePanel() {
			this.expanded = !this.expanded;
		},
		
		switchScenario(scenarioId) {
			if (scenarioId === this.currentScenario) return;
			
			const success = setMockScenario(scenarioId);
			if (success) {
				this.currentScenario = scenarioId;
				uni.showToast({
					title: `已切换到场景 ${scenarioId}`,
					icon: 'success'
				});
				
				// 延迟500ms后刷新当前页面
				setTimeout(() => {
					const pages = getCurrentPages();
					if (pages.length > 0) {
						const currentPage = pages[pages.length - 1];
						// 触发页面的 onShow 方法刷新数据
						if (currentPage.$vm && typeof currentPage.$vm.$options.onShow === 'function') {
							currentPage.$vm.$options.onShow.call(currentPage.$vm);
						}
					}
				}, 500);
			}
		},
		
		triggerRefundNotice() {
			uni.showToast({
				title: '跳转到公排状态页查看退款弹窗',
				icon: 'none',
				duration: 2000
			});
			
			setTimeout(() => {
				uni.navigateTo({
					url: '/pages/queue/status?show_refund=1'
				});
				this.expanded = false;
			}, 1500);
		},
		
		clearGuideFlag() {
			uni.removeStorageSync('hjf_guide_read');
			uni.showToast({
				title: '引导标记已清除',
				icon: 'success'
			});
			setTimeout(() => {
				uni.reLaunch({
					url: '/pages/guide/hjf_intro'
				});
			}, 1000);
		},
		
		navigateTo(path) {
			this.expanded = false;
			
			// 处理 TabBar 页面
			const tabBarPages = ['/pages/index/index', '/pages/user/index'];
			if (tabBarPages.includes(path)) {
				uni.switchTab({ url: path });
			} else {
				uni.navigateTo({ url: path });
			}
		}
	}
};
</script>

<style scoped lang="scss">
.hjf-demo-container {
	position: fixed;
	z-index: 9999;
}

.demo-fab {
	position: fixed;
	right: 30rpx;
	bottom: 200rpx;
	width: 100rpx;
	height: 100rpx;
	border-radius: 50%;
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	box-shadow: 0 8rpx 20rpx rgba(102, 126, 234, 0.4);
	display: flex;
	align-items: center;
	justify-content: center;
	z-index: 10000;
	transition: all 0.3s ease;
}

.demo-fab:active {
	transform: scale(0.95);
}

.demo-mask {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: rgba(0, 0, 0, 0.5);
	z-index: 9998;
}

.demo-panel {
	position: fixed;
	right: 30rpx;
	bottom: 200rpx;
	width: 600rpx;
	max-height: 1000rpx;
	background: #fff;
	border-radius: 24rpx;
	box-shadow: 0 20rpx 60rpx rgba(0, 0, 0, 0.3);
	z-index: 9999;
	overflow: hidden;
	animation: slideIn 0.3s ease;
}

@keyframes slideIn {
	from {
		opacity: 0;
		transform: translateY(20rpx);
	}
	to {
		opacity: 1;
		transform: translateY(0);
	}
}

.panel-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 30rpx 32rpx;
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	color: #fff;
}

.panel-title {
	font-size: 32rpx;
	font-weight: 600;
}

.panel-body {
	max-height: 880rpx;
	overflow-y: auto;
	padding: 0 0 20rpx 0;
}

.section {
	padding: 24rpx 32rpx;
	border-bottom: 1px solid #f0f0f0;
}

.section-title {
	font-size: 28rpx;
	font-weight: 600;
	color: #333;
	margin-bottom: 20rpx;
}

.scenario-btns {
	display: flex;
	flex-direction: column;
	gap: 16rpx;
}

.scenario-btn {
	padding: 24rpx 20rpx;
	background: #f5f5f5;
	border-radius: 16rpx;
	border: 2px solid transparent;
	transition: all 0.3s ease;
}

.scenario-btn.active {
	background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
	border-color: #667eea;
}

.scenario-label {
	display: block;
	font-size: 28rpx;
	font-weight: 600;
	color: #333;
	margin-bottom: 8rpx;
}

.scenario-desc {
	display: block;
	font-size: 24rpx;
	color: #999;
}

.action-btns {
	display: flex;
	gap: 16rpx;
}

.action-btn {
	flex: 1;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	padding: 24rpx;
	background: #f5f5f5;
	border-radius: 16rpx;
	transition: all 0.3s ease;
}

.action-btn:active {
	background: #e8e8e8;
	transform: scale(0.98);
}

.action-label {
	font-size: 24rpx;
	color: #666;
	margin-top: 8rpx;
}

.nav-scroll {
	max-height: 400rpx;
}

.nav-item {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 24rpx 20rpx;
	background: #f5f5f5;
	border-radius: 12rpx;
	margin-bottom: 12rpx;
	transition: all 0.3s ease;
}

.nav-item:active {
	background: #e8e8e8;
}

.nav-label {
	font-size: 26rpx;
	color: #333;
}

.text-white {
	color: #fff;
}
</style>
