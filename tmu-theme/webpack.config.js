const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

module.exports = (env, argv) => {
  const isProduction = argv.mode === 'production';
  
  return {
    entry: {
      main: './assets/src/js/main.js',
      admin: './assets/src/js/admin.js',
      'tmdb-sync': './assets/src/js/tmdb-sync.js',
      'admin-styles': './assets/src/scss/admin.scss',
      blocks: './assets/src/blocks/index.js',
      'blocks-editor': './assets/src/blocks/editor.scss'
    },
    output: {
      path: path.resolve(__dirname, 'assets/build'),
      filename: 'js/[name].js',
      clean: true
    },
    module: {
      rules: [
        {
          test: /\.jsx?$/,
          exclude: /node_modules/,
          use: {
            loader: 'babel-loader',
            options: {
              presets: ['@babel/preset-env', '@babel/preset-react']
            }
          }
        },
        {
          test: /\.(css|scss)$/,
          use: [
            MiniCssExtractPlugin.loader,
            'css-loader',
            {
              loader: 'postcss-loader',
              options: {
                postcssOptions: {
                  plugins: [
                    require('tailwindcss'),
                    require('autoprefixer'),
                  ]
                }
              }
            },
            {
              loader: 'sass-loader',
              options: {
                api: 'modern',
                sassOptions: {
                  silenceDeprecations: ['legacy-js-api']
                }
              }
            }
          ]
        }
      ]
    },
    plugins: [
      new MiniCssExtractPlugin({
        filename: 'css/[name].css'
      })
    ],
    optimization: {
      minimize: isProduction
    },
    externals: {
      '@wordpress/blocks': 'wp.blocks',
      '@wordpress/element': 'wp.element',
      '@wordpress/components': 'wp.components',
      '@wordpress/block-editor': 'wp.blockEditor',
      '@wordpress/data': 'wp.data',
      '@wordpress/i18n': 'wp.i18n',
      '@wordpress/api-fetch': 'wp.apiFetch',
      '@wordpress/editor': 'wp.editor',
      '@wordpress/hooks': 'wp.hooks',
      '@wordpress/url': 'wp.url',
      '@wordpress/date': 'wp.date'
    },
    devtool: isProduction ? false : 'source-map'
  };
};