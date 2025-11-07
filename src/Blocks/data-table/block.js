/**
 * Wilson API Data Table Block
 *
 * @package WilsonApiPlugin
 */

import { registerBlockType } from "@wordpress/blocks";
import { InspectorControls, useBlockProps } from "@wordpress/block-editor";
import { PanelBody, ToggleControl, Spinner, Notice } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { useState, useEffect } from "@wordpress/element";

/**
 * Register block type
 */
registerBlockType("wilson-api/data-table", {
  title: __("Wilson API Data Table", "wilson-api-plugin"),
  description: __("Display data from Wilson API in a table format", "wilson-api-plugin"),
  category: "widgets",
  icon: "list-view",
  keywords: [__("api", "wilson-api-plugin"), __("data", "wilson-api-plugin"), __("table", "wilson-api-plugin")],
  attributes: {
    visibleColumns: {
      type: "object",
      default: {},
    },
    showHeader: {
      type: "boolean",
      default: true,
    },
    blockId: {
      type: "string",
      default: "",
    },
  },

  /**
   * Edit function - renders in the block editor
   */
  edit: ({ attributes, setAttributes }) => {
    const { visibleColumns, showHeader, blockId } = attributes;
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [columns, setColumns] = useState([]);

    const blockProps = useBlockProps({
      className: "wilson-api-block-editor",
    });

    // Generate block ID on first render
    useEffect(() => {
      if (!blockId) {
        setAttributes({
          blockId: `wilson-api-block-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`,
        });
      }
    }, []);

    // Fetch data from API
    useEffect(() => {
      fetchData();
    }, []);

    /**
     * Fetch data from AJAX endpoint
     */
    const fetchData = async () => {
      setLoading(true);
      setError(null);

      try {
        const response = await fetch(`${wilsonApiBlock.ajaxUrl}?action=${wilsonApiBlock.action}`, {
          method: "GET",
          headers: {
            "Content-Type": "application/json",
          },
        });

        if (!response.ok) {
          throw new Error(__("Failed to fetch data", "wilson-api-plugin"));
        }

        const result = await response.json();

        if (!result.success) {
          throw new Error(result.data?.message || __("Unknown error", "wilson-api-plugin"));
        }

        const fetchedData = result.data.data;
        setData(fetchedData);

        // Extract column names
        if (Array.isArray(fetchedData) && fetchedData.length > 0) {
          const cols = Object.keys(fetchedData[0]);
          setColumns(cols);

          // Initialize visible columns if not set
          if (Object.keys(visibleColumns).length === 0) {
            const initialVisible = {};
            cols.forEach((col) => {
              initialVisible[col] = true;
            });
            setAttributes({ visibleColumns: initialVisible });
          }
        } else if (fetchedData && typeof fetchedData === "object") {
          const cols = Object.keys(fetchedData);
          setColumns(cols);

          if (Object.keys(visibleColumns).length === 0) {
            const initialVisible = {};
            cols.forEach((col) => {
              initialVisible[col] = true;
            });
            setAttributes({ visibleColumns: initialVisible });
          }
        }
      } catch (err) {
        setError(err.message);
      } finally {
        setLoading(false);
      }
    };

    /**
     * Toggle column visibility
     */
    const toggleColumn = (columnName) => {
      setAttributes({
        visibleColumns: {
          ...visibleColumns,
          [columnName]: !visibleColumns[columnName],
        },
      });
    };

    /**
     * Render table
     */
    const renderTable = () => {
      if (!data) {
        return <p>{__("No data available", "wilson-api-plugin")}</p>;
      }

      // Handle array of objects
      if (Array.isArray(data)) {
        const visibleCols = columns.filter((col) => visibleColumns[col] !== false);

        if (visibleCols.length === 0) {
          return (
            <Notice status="warning" isDismissible={false}>
              {__("Please enable at least one column in the block settings.", "wilson-api-plugin")}
            </Notice>
          );
        }

        return (
          <div className="wilson-api-table-wrapper">
            <table className="wilson-api-table">
              {showHeader && (
                <thead>
                  <tr>
                    {visibleCols.map((col) => (
                      <th key={col}>{col.replace(/_/g, " ").replace(/\b\w/g, (l) => l.toUpperCase())}</th>
                    ))}
                  </tr>
                </thead>
              )}
              <tbody>
                {data.map((row, index) => (
                  <tr key={index}>
                    {visibleCols.map((col) => (
                      <td key={col}>{typeof row[col] === "object" ? JSON.stringify(row[col]) : String(row[col] ?? "")}</td>
                    ))}
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        );
      }

      // Handle single object
      const visibleCols = columns.filter((col) => visibleColumns[col] !== false);

      if (visibleCols.length === 0) {
        return (
          <Notice status="warning" isDismissible={false}>
            {__("Please enable at least one column in the block settings.", "wilson-api-plugin")}
          </Notice>
        );
      }

      return (
        <div className="wilson-api-table-wrapper">
          <table className="wilson-api-table">
            <tbody>
              {visibleCols.map((key) => (
                <tr key={key}>
                  {showHeader && <th>{key.replace(/_/g, " ").replace(/\b\w/g, (l) => l.toUpperCase())}</th>}
                  <td>{typeof data[key] === "object" ? JSON.stringify(data[key]) : String(data[key] ?? "")}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      );
    };

    return (
      <>
        <InspectorControls>
          <PanelBody title={__("Display Settings", "wilson-api-plugin")}>
            <ToggleControl label={__("Show Table Header", "wilson-api-plugin")} checked={showHeader} onChange={() => setAttributes({ showHeader: !showHeader })} />
          </PanelBody>

          {columns.length > 0 && (
            <PanelBody title={__("Column Visibility", "wilson-api-plugin")} initialOpen={false}>
              <p className="components-base-control__help">{__("Toggle which columns to display in the table", "wilson-api-plugin")}</p>
              {columns.map((col) => (
                <ToggleControl key={col} label={col.replace(/_/g, " ").replace(/\b\w/g, (l) => l.toUpperCase())} checked={visibleColumns[col] !== false} onChange={() => toggleColumn(col)} />
              ))}
            </PanelBody>
          )}
        </InspectorControls>

        <div {...blockProps}>
          <div className="wilson-api-block-header">
            <h3>{__("Wilson API Data Table", "wilson-api-plugin")}</h3>
            <button type="button" className="components-button is-secondary is-small" onClick={fetchData} disabled={loading}>
              {loading ? __("Refreshing...", "wilson-api-plugin") : __("Refresh", "wilson-api-plugin")}
            </button>
          </div>

          {loading && (
            <div className="wilson-api-block-loading">
              <Spinner />
              <p>{__("Loading data...", "wilson-api-plugin")}</p>
            </div>
          )}

          {error && (
            <Notice status="error" isDismissible={false}>
              {error}
            </Notice>
          )}

          {!loading && !error && renderTable()}
        </div>
      </>
    );
  },

  /**
   * Save function - renders in the frontend
   * Returns null because we use PHP render callback
   */
  save: () => {
    return null;
  },
});
