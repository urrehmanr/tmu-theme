const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const defaultConfig = require('@wordpress/scripts/config/webpack.config');

const isProduction = process.env.NODE_ENV === 'production';

module.exports = {
  ...defaultConfig,
  entry: {
    'frontend': './assets/src/js/frontend.js',
    'admin': './assets/src/js/admin.js',
    'blocks': './assets/src/blocks/index.js',
    'editor': './assets/src/scss/editor.scss',
    'style': './assets/src/scss/style.scss'
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
            presets: [
              '@wordpress/babel-preset-default'
            ]
          }
        }
      },
      {
        test: /\.s?css$/,
        use: [
          isProduction ? MiniCssExtractPlugin.loader : 'style-loader',
          'css-loader',
          {
            loader: 'postcss-loader',
            options: {
              postcssOptions: {
                plugins: [
                  require('tailwindcss'),
                  require('autoprefixer')
                ]
              }
            }
          },
          'sass-loader'
        ]
      },
      {
        test: /\.(png|jpg|jpeg|gif|svg)$/,
        type: 'asset/resource',
        generator: {
          filename: 'images/[name][ext]'
        }
      },
      {
        test: /\.(woff|woff2|eot|ttf|otf)$/,
        type: 'asset/resource',
        generator: {
          filename: 'fonts/[name][ext]'
        }
      }
    ]
  },
  plugins: [
    ...defaultConfig.plugins,
    new MiniCssExtractPlugin({
      filename: 'css/[name].css'
    })
  ],
  resolve: {
    extensions: ['.js', '.jsx', '.json'],
    alias: {
      '@': path.resolve(__dirname, 'assets/src'),
      '@components': path.resolve(__dirname, 'assets/src/js/components'),
      '@blocks': path.resolve(__dirname, 'assets/src/blocks'),
      '@utils': path.resolve(__dirname, 'assets/src/js/utils'),
      '@scss': path.resolve(__dirname, 'assets/src/scss')
    }
  },
  externals: {
    'react': 'React',
    'react-dom': 'ReactDOM',
    '@wordpress/blocks': ['wp', 'blocks'],
    '@wordpress/block-editor': ['wp', 'blockEditor'],
    '@wordpress/components': ['wp', 'components'],
    '@wordpress/data': ['wp', 'data'],
    '@wordpress/element': ['wp', 'element'],
    '@wordpress/i18n': ['wp', 'i18n'],
    '@wordpress/api-fetch': ['wp', 'apiFetch']
  },
  optimization: {
    splitChunks: {
      cacheGroups: {
        vendor: {
          test: /[\\/]node_modules[\\/]/,
          name: 'vendors',
          chunks: 'all'
        }
      }
    }
  },
  devServer: {
    contentBase: path.join(__dirname, 'assets/build'),
    compress: true,
    port: 3000,
    hot: true,
    watchContentBase: true,
    open: false
  }
};