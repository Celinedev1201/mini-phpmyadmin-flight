import AddColumn from "./AddColumn";

import { useEffect, useState } from "react";

function TableStructure({ database, table }) {
  const [columns, setColumns] = useState([]);
  const [refreshKey, setRefreshKey] = useState(0);

  useEffect(() => {
    async function loadColumns() {
      try {
        const response = await fetch(
          `/api/databases/${encodeURIComponent(database)}/tables/${encodeURIComponent(table)}/columns`,
        );

        if (!response.ok) {
          throw new Error(`Erreur HTTP ${response.status}`);
        }

        const data = await response.json();
        setColumns(data.columns);
      } catch (error) {
        console.error("Chargement des colonnes impossible :", error);
      }
    }

    setColumns([]);
    loadColumns();
  }, [database, table, refreshKey]);

  return (
    <section className="table-panel">
      <div className="panel-heading">
        <span>Structure de {table}</span>
        <span>{database}</span>
      </div>

      <AddColumn
        database={database}
        table={table}
        onCreated={() => setRefreshKey((value) => value + 1)}
      />

      <div className="structure-scroll">
        <table className="structure-table">
          <thead>
            <tr>
              <th scope="col">Colonne</th>
              <th scope="col">Type</th>
              <th scope="col">Valeur NULL</th>
              <th scope="col">Clé</th>
              <th scope="col">Extra</th>
            </tr>
          </thead>

          <tbody>
            {columns.map((column) => (
              <tr key={column.COLUMN_NAME}>
                <td>{column.COLUMN_NAME}</td>
                <td>{column.COLUMN_TYPE}</td>
                <td>
                  {column.IS_NULLABLE === "YES" ? "Autorisée" : "Interdite"}
                </td>
                <td>
                  {column.COLUMN_KEY === "PRI"
                    ? "Primaire"
                    : column.COLUMN_KEY || "—"}
                </td>
                <td>{column.EXTRA || "—"}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </section>
  );
}

export default TableStructure;
