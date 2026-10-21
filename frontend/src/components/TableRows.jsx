import { useEffect, useState } from "react";

function TableRows({ database, table, refreshKey }) {
  const [rows, setRows] = useState([]);

  useEffect(() => {
    let active = true;

    async function loadRows() {
      try {
        const response = await fetch(
          `/api/databases/${encodeURIComponent(database)}/tables/${encodeURIComponent(table)}/rows`
        );

        const data = await response.json();

        if (!response.ok) {
          throw new Error(data.message || `Erreur HTTP ${response.status}`);
        }

        if (active) {
          setRows(data.rows);
        }
      } catch (error) {
        console.error("Chargement des lignes impossible :", error);
      }
      
    }

    setRows([]);
    loadRows();


    const columnNames = rows.length > 0 ? Object.keys(rows[0]) : [];

    return () => {
      active = false;
    };
  }, [database, table, refreshKey]);
  
  const columnNames = rows.length > 0 ? Object.keys(rows[0]) : [];



  return (
    <section className="table-panel">
      <div className="panel-heading">
        <span>Données de {table}</span>
        <span>{rows.length} lignes affichées · maximum 50</span>
      </div>

      {rows.length > 0 ? (
  <div className="structure-scroll">
    <table className="structure-table">
      <thead>
        <tr>
          {columnNames.map((column) => (
            <th key={column} scope="col">
              {column}
            </th>
          ))}
        </tr>
      </thead>

      <tbody>
        {rows.map((row, index) => (
          <tr key={index}>
            {columnNames.map((column) => (
              <td key={column}>
                {row[column] === null ? <em>NULL</em> : String(row[column])}
              </td>
            ))}
          </tr>
        ))}
      </tbody>
    </table>
  </div>
) : (
  <p className="empty-message">Aucune ligne à afficher.</p>
)}

    </section>
  );
}

export default TableRows;