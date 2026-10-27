import { useEffect, useState } from "react";
import { request, tablePath } from "../services/api";
import Feedback from "./Feedback";
import DeleteButton from "./DeleteButton";
import RowForm from "./RowForm";
// Affiche au maximum 50 lignes et prépare les actions sur la ligne choisie.
function TableRows({ database, table, columns, refreshKey }) {
  const [rows, setRows] = useState([]);
  const [primaryKey, setPrimaryKey] = useState([]);
  const [reason, setReason] = useState("");
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");
  const [editing, setEditing] = useState(null);
  const [version, setVersion] = useState(0);
  const path = `${tablePath(database, table)}/rows`;
  // Relance le chargement quand les valeurs entre crochets changent.
  useEffect(() => {
    let active = true;
    // Charge les lignes et les informations qui permettent de les modifier ou de les supprimer.
    async function loadRows() {
      setLoading(true);
      setError("");
      try {
        const data = await request(path);
        if (active) {
          // La clé React sert à l’affichage seulement ; la clé SQL reste séparée.
          setRows(data.rows.map((row) => ({ row, renderKey: crypto.randomUUID() })));
          setPrimaryKey(data.primaryKey);
          setReason(data.actionDisabledReason);
        }
      } catch (error) {
        if (active) setError(error.message);
      } finally {
        if (active) setLoading(false);
      }
    }
    loadRows();
    return () => { active = false; };
  }, [path, refreshKey, version]);
  // Ferme l’édition et change le compteur pour déclencher un nouveau chargement des lignes.
  function reloadRows() {
    setEditing(null);
    setLoading(true);
    setVersion((previous) => previous + 1);
  }
  // Affiche le résultat de l’action puis recharge les lignes depuis la base.
  function rowChanged(data) {
    setSuccess(data.message);
    reloadRows();
  }

  const columnNames = rows.length > 0 ? Object.keys(rows[0].row) : [];
  const disabled = loading || Boolean(error) || Boolean(reason) || Boolean(editing);
  return (
    <section className="table-panel">
      <div className="panel-heading">
        <span>Données de {table}</span><span>{rows.length} lignes affichées · maximum 50</span>
      </div>
      <Feedback loading={loading} error={error} success={success} />
      <button className="action-button" disabled={loading} onClick={reloadRows}>Actualiser les lignes</button>
      {reason && <p className="empty-message">{reason}</p>}
      {editing && <RowForm key={editing.renderKey} database={database} table={table}
        row={editing.row} rowKey={editing.rowKey} columns={columns}
        onSaved={rowChanged} onCancel={() => setEditing(null)} />}
      {rows.length > 0 && <div className="structure-scroll">
        <table className="structure-table">
          <thead>
            <tr>{columnNames.map((column) => <th key={column}>{column}</th>)}<th>Actions</th></tr>
          </thead>
          <tbody>
            {rows.map(({ row, renderKey }) => {
              // Cette clé contient les valeurs SQL qui identifient exactement la ligne à modifier.
              const rowKey = {};
              for (const name of primaryKey) rowKey[name] = row[name];
              return (
                <tr key={renderKey}>
                  {columnNames.map((column) => (
                    <td key={column}>{row[column] === null ? <em>NULL</em> : String(row[column])}</td>
                  ))}
                  <td>
                    <button className="action-button" disabled={disabled}
                      onClick={() => setEditing({ row, rowKey, renderKey })}>Modifier</button>
                    <DeleteButton path={path} target={`la ligne de clé ${JSON.stringify(rowKey)}`}
                      body={{ key: rowKey }} onDeleted={rowChanged} disabled={disabled} />
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </div>}
      {!loading && !error && rows.length === 0 && <p className="empty-message">Aucune ligne à afficher.</p>}
    </section>
  );
}

export default TableRows;
