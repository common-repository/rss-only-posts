module.exports = {
	entry: {
		'/': './rssonlypost.js'
	},
	output: {
		path: __dirname,
		filename: 'rssonlypost.min.js',
	},
	module: {
		rules: [
			{
				test: /\.(js|jsx)$/,
				use: { 
					loader: "babel-loader",
				},
				exclude: /(node_modules|bower_components)/,
			}
		]
	}
};
