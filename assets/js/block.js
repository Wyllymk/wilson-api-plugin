/**
 * Frontend JavaScript for Wilson API Data Table Block
 *
 * @package WilsonApiPlugin
 */

(function ($) {
  "use strict";

  /**
   * Initialize block when DOM is ready
   */
  $(document).ready(function () {
    $(".wilson-api-block").each(function () {
      const $block = $(this);
      const blockId = $block.data("block-id");
      const sanitizedId = $block.data("sanitized-id");

      // Get localized data using sanitized ID
      const blockData = window["wilsonApiBlockData_" + sanitizedId];

      if (!blockData) {
        console.error("Wilson API Block: Missing block data for", blockId);
        // Try to fetch data directly without localized data
        const fallbackData = {
          ajaxUrl: window.location.origin + "/wp-admin/admin-ajax.php",
          action: "wilson_api_get_data",
          blockId: blockId,
          visibleColumns: $block.data("visible-columns") || {},
          showHeader: $block.data("show-header") === "1" || $block.data("show-header") === 1,
          i18n: {
            loading: "Loading data...",
            error: "Error loading data. Please try again later.",
            noData: "No data available.",
          },
        };
        initBlock($block, fallbackData);
        return;
      }

      // Initialize block
      initBlock($block, blockData);
    });
  });

  /**
   * Initialize individual block
   *
   * @param {jQuery} $block Block element
   * @param {Object} blockData Block configuration data
   */
  function initBlock($block, blockData) {
    // Fetch data from API
    fetchData($block, blockData);
  }

  /**
   * Fetch data from AJAX endpoint
   *
   * @param {jQuery} $block Block element
   * @param {Object} blockData Block configuration data
   */
  function fetchData($block, blockData) {
    $.ajax({
      url: blockData.ajaxUrl,
      type: "GET",
      data: {
        action: blockData.action,
      },
      dataType: "json",
      success: function (response) {
        if (response.success && response.data && response.data.data) {
          renderTable($block, response.data.data, blockData);
        } else {
          showError($block, blockData.i18n.error);
        }
      },
      error: function (xhr, status, error) {
        console.error("Wilson API Block Error:", error);
        showError($block, blockData.i18n.error);
      },
    });
  }

  /**
   * Render data table
   *
   * @param {jQuery} $block Block element
   * @param {Array|Object} data Data to display
   * @param {Object} blockData Block configuration data
   */
  function renderTable($block, data, blockData) {
    const visibleColumns = blockData.visibleColumns;
    const showHeader = blockData.showHeader;

    // Clear loading state
    $block.empty();

    // Handle empty data
    if (!data || (Array.isArray(data) && data.length === 0)) {
      $block.html('<p class="wilson-api-no-data">' + escapeHtml(blockData.i18n.noData) + "</p>");
      return;
    }

    // Create table wrapper
    const $wrapper = $("<div>").addClass("wilson-api-table-wrapper");
    const $table = $("<table>").addClass("wilson-api-table");

    // Handle array of objects
    if (Array.isArray(data)) {
      renderArrayTable($table, data, visibleColumns, showHeader);
    } else {
      // Handle single object
      renderObjectTable($table, data, visibleColumns, showHeader);
    }

    $wrapper.append($table);
    $block.append($wrapper);
  }

  /**
   * Render table for array data
   *
   * @param {jQuery} $table Table element
   * @param {Array} data Array of data objects
   * @param {Object} visibleColumns Column visibility settings
   * @param {boolean} showHeader Whether to show table header
   */
  function renderArrayTable($table, data, visibleColumns, showHeader) {
    // Get all columns from first item
    const allColumns = Object.keys(data[0] || {});

    // Filter visible columns
    const columns = allColumns.filter(function (col) {
      return visibleColumns[col] !== false;
    });

    // Create header
    if (showHeader && columns.length > 0) {
      const $thead = $("<thead>");
      const $headerRow = $("<tr>");

      columns.forEach(function (col) {
        const $th = $("<th>").text(formatColumnName(col));
        $headerRow.append($th);
      });

      $thead.append($headerRow);
      $table.append($thead);
    }

    // Create body
    const $tbody = $("<tbody>");

    data.forEach(function (row) {
      const $tr = $("<tr>");

      columns.forEach(function (col) {
        const value = row[col];
        const $td = $("<td>");

        if (typeof value === "object" && value !== null) {
          $td.text(JSON.stringify(value));
        } else {
          $td.text(String(value ?? ""));
        }

        $tr.append($td);
      });

      $tbody.append($tr);
    });

    $table.append($tbody);
  }

  /**
   * Render table for object data
   *
   * @param {jQuery} $table Table element
   * @param {Object} data Data object
   * @param {Object} visibleColumns Column visibility settings
   * @param {boolean} showHeader Whether to show table header
   */
  function renderObjectTable($table, data, visibleColumns, showHeader) {
    const $tbody = $("<tbody>");

    Object.keys(data).forEach(function (key) {
      // Skip if column is hidden
      if (visibleColumns[key] === false) {
        return;
      }

      const $tr = $("<tr>");

      // Add header cell if enabled
      if (showHeader) {
        const $th = $("<th>").text(formatColumnName(key));
        $tr.append($th);
      }

      // Add data cell
      const value = data[key];
      const $td = $("<td>");

      if (typeof value === "object" && value !== null) {
        // Format complex data nicely
        const $code = $("<code>").text(JSON.stringify(value, null, 2));
        $td.append($code);
      } else {
        $td.text(String(value ?? ""));
      }

      $tr.append($td);
      $tbody.append($tr);
    });

    $table.append($tbody);
  }

  /**
   * Show error message
   *
   * @param {jQuery} $block Block element
   * @param {string} message Error message
   */
  function showError($block, message) {
    $block.empty();

    const $error = $("<div>").addClass("wilson-api-error").text(message);

    $block.append($error);
  }

  /**
   * Format column name for display
   *
   * @param {string} columnName Column name
   * @return {string} Formatted column name
   */
  function formatColumnName(columnName) {
    return columnName.replace(/_/g, " ").replace(/\b\w/g, function (l) {
      return l.toUpperCase();
    });
  }

  /**
   * Escape HTML to prevent XSS
   *
   * @param {string} text Text to escape
   * @return {string} Escaped text
   */
  function escapeHtml(text) {
    const map = {
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      '"': "&quot;",
      "'": "&#039;",
    };

    return String(text).replace(/[&<>"']/g, function (m) {
      return map[m];
    });
  }
})(jQuery);
