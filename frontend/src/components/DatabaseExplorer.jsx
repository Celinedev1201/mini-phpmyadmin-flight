import { useEffect, useState } from "react";
import { request, databasePath } from "../services/api";
import CreateTable from "./CreateTable";
import DeleteButton from "./DeleteButton";
import TableStructure from "./TableStructure";
import Feedback from "./Feedback";
import CreateBackup from "./CreateBackup";
import BackupHistory from "./BackupHistory";

function DatabaseExplorer({ database, onDeleted }) {
  const [tables, setTables] = useState([]);
  const [selectedTable, setSelectedTable] = useState("");
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");
  const [backupsVersion, setBackupsVersion] = useState(0);

  async function loadTables() {
    setLoading(true);
    setError("");
    try {
      const data = await request(`${databasePath(database)}/tables`);
      setTables(data.tables);
      // Une table supprimée ne doit plus rester sélectionnée.
      setSelectedTable((previous) =>
        data.tables.includes(previous) ? previous : "",
      );
    } catch (error) {
      setError(error.message);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    loadTables();
  }, [database]);

  function tableChanged(data) {
    setSelectedTable(data.name || "");
    setSuccess(data.message);
    loadTables();
  }

  return (
    <>
      <div className="section-heading">
        <div>
          <p className="eyebrow">Explorateur / MySQL</p>
          <h2 id="explorer-title">{database}</h2>
          <p className="section-description">
            Les tables et vues de votre base, réunies au même endroit.
          </p>
        </div>
        <div className="table-count">
          <strong>{tables.length}</strong>
          <span>tables & vues</span>
        </div>
      </div>
      <CreateBackup
        database={database}
        onCreated={() => setBackupsVersion((value) => value + 1)}
      />

      <BackupHistory
        key={database}
        database={database}
        refreshKey={backupsVersion}
      />

      <div className="resource-actions">
        <DeleteButton
          path={databasePath(database)}
          target={database}
          explicitName
          onDeleted={onDeleted}
        />
        <span>Suppression de la base entière</span>
      </div>
      <Feedback success={success} />
      {selectedTable && (
        <TableStructure
          key={selectedTable}
          database={database}
          table={selectedTable}
          onTableChanged={tableChanged}
        />
      )}
      <div className="table-panel">
        <CreateTable database={database} onCreated={loadTables} />
        <div className="panel-heading">
          <span>Structure de la base</span>
          <span>Nom</span>
        </div>
        <Feedback loading={loading} error={error} />
        <button
          className="action-button"
          disabled={loading}
          onClick={loadTables}
        >
          Actualiser les tables
        </button>
        <ul className="table-list">
          {tables.map((table, index) => (
            <li key={table} className="table-row">
              <span className="table-number">
                {String(index + 1).padStart(2, "0")}
              </span>
              <span className="table-symbol" aria-hidden="true">
                ▦
              </span>
              <button
                className="table-name table-select"
                onClick={() => setSelectedTable(table)}
              >
                {table}
              </button>
            </li>
          ))}
        </ul>
        {!loading && !error && tables.length === 0 && (
          <p className="empty-message">
            Cette base ne contient aucune table accessible.
          </p>
        )}
      </div>
    </>
  );
}

export default DatabaseExplorer;
