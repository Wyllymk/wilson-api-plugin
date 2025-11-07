/**
 * Webpack Configuration for Wilson API Plugin
 *
 * @package WilsonApiPlugin
 */

const defaultConfig = require("@wordpress/scripts/config/webpack.config");
const path = require("path");

module.exports = {
  ...defaultConfig,
  entry: {
    block: path.resolve(__dirname, "src/blocks/data-table/block.js"),
  },
  output: {
    filename: "[name].js",
    path: path.resolve(__dirname, "build"),
  },
};
