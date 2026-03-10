const HX_BABEL = '/Applications/HBuilderX.app/Contents/HBuilderX/plugins/uniapp-cli/node_modules'

module.exports = {
	plugins: [
		require.resolve('@babel/plugin-proposal-optional-chaining', { paths: [HX_BABEL] }),
		require.resolve('@babel/plugin-proposal-nullish-coalescing-operator', { paths: [HX_BABEL] })
	]
}
