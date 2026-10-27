import { useEffect, useState } from "react";
import { request, tablePath } from "../services/api";
import AddColumn from "./AddColumn";
import ColumnActions from "./ColumnActions";
import RowForm from "./RowForm";
import TableActions from "./TableActions";
import TableRows from "./TableRows";
import Feedback from "./Feedback";
// Affiche les colonnes et rassemble les formulaires et les données de la table.
function TableStructure({ database, table, onTableChanged }) {
  const [columns, setColumns] = useState([]);
  const [isTable, setIsTable] = useState(false);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");
  const [structureVersion, setStructureVersion] = useState(0);
  const [rowsVersion, setRowsVersion] = useState(0);
  // Charge les colonnes et vérifie si la sélection est une table modifiable ou une vue.
  async function loadColumns() {
    setLoading(true);
    setError("");
    try {
      const data = await request(`${tablePath(database, table)}/columns`);
      setColumns(data.columns);
      setIsTable(data.isTable);
    } catch (error) {
      setError(error.message);
    } finally {
      setLoading(false);
    }
  }
  // Relance le chargement quand les valeurs entre crochets changent.
  useEffect(() => { loadColumns(); }, [database, table]);
  // Affiche le résultat et recharge la structure après un changement de colonne.
  function columnChanged(data) {
    setSuccess(data.message);
    // Reconstruit aussi les formulaires, car les noms et la clé peuvent changer.
    setStructureVersion((previous) => previous + 1);
    loadColumns();
  }

  return (
    <section className="table-panel">
      <div className="panel-heading"><span>Structure de {table}</span><span>{database}</span></div>
      <Feedback loading={loading} error={error} success={success} />
      <button className="action-button" onClick={loadColumns} disabled={loading}>Actualiser la structure</button>
      {isTable && <TableActions database={database} table={table} onChanged={onTableChanged} />}
      {isTable && <AddColumn database={database} table={table} onCreated={columnChanged} />}
      <div className="structure-scroll">
        <table className="structure-table">
          <thead>
            <tr>
              <th>Colonne</th><th>Type</th><th>Valeur NULL</th><th>Clé</th><th>Extra</th><th>Actions</th>
            </tr>
          </thead>
          <tbody>
            {columns.map((column) => (
              <tr key={column.COLUMN_NAME}>
                <td>{column.COLUMN_NAME}</td><td>{column.COLUMN_TYPE}</td>
                <td>{column.IS_NULLABLE === "YES" ? "Autorisée" : "Interdite"}</td>
                <td>{column.COLUMN_KEY === "PRI" ? "Primaire" : column.COLUMN_KEY || "—"}</td>
                <td>{column.EXTRA || "—"}</td>
                <td><ColumnActions column={column} database={database} table={table}
                  disabled={loading || !isTable} onChanged={columnChanged} /></td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      {!loading && !error && isTable && (
        <RowForm key={`insert-${structureVersion}`} database={database} table={table} columns={columns}
          onSaved={() => setRowsVersion((previous) => previous + 1)} />
      )}
      {!loading && !error && (
        <TableRows key={`rows-${structureVersion}`} database={database} table={table}
          columns={columns} refreshKey={rowsVersion} />
      )}
    </section>
  );
}

export default TableStructure;
