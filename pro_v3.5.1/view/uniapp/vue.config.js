module.exports = {
	productionSourceMap: false, // 生产打包时不输出map文件，增加打包速度
	devServer: {
		port: 8080,
		proxy: {
			'/api': {
				target: 'http://127.0.0.1',
				changeOrigin: true
			},
			'/uploads': {
				target: 'http://127.0.0.1',
				changeOrigin: true
			},
			'/statics': {
				target: 'http://127.0.0.1',
				changeOrigin: true
			}
		}
	},
	chainWebpack: config => {
		const HX_BABEL = '/Applications/HBuilderX.app/Contents/HBuilderX/plugins/uniapp-cli/node_modules'
		const optionalChaining = require.resolve('@babel/plugin-proposal-optional-chaining', { paths: [HX_BABEL] })
		const nullishCoalescing = require.resolve('@babel/plugin-proposal-nullish-coalescing-operator', { paths: [HX_BABEL] })
		config.module.rule('js').use('babel-loader').tap(options => {
			options = options || {}
			options.plugins = options.plugins || []
			const pluginPaths = options.plugins.map(p => (Array.isArray(p) ? p[0] : p))
			if (!pluginPaths.includes(optionalChaining)) {
				options.plugins.push(optionalChaining)
			}
			if (!pluginPaths.includes(nullishCoalescing)) {
				options.plugins.push(nullishCoalescing)
			}
			return options
		})
	},
	configureWebpack: config => {
		if (process.env.NODE_ENV === 'production') {
			config.optimization.minimizer[0].options.terserOptions.compress.warnings = false
			config.optimization.minimizer[0].options.terserOptions.compress.drop_console = true
			config.optimization.minimizer[0].options.terserOptions.compress.drop_debugger = true
			config.optimization.minimizer[0].options.terserOptions.compress.pure_funcs = ['console.log']
		}
	}
}
