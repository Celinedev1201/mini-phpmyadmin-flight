import { useEffect, useState } from "react";
import { request, databasePath } from "../../services/api";
import CreateTable from "../tables/CreateTable";
import DeleteButton from "../common/DeleteButton";
import TableStructure from "../tables/TableStructure";
import Feedback from "../common/Feedback";
import CopyDatabaseToPostgres from "./CopyDatabaseToPostgres";
import CreateBackup from "../backups/CreateBackup";
import BackupHistory from "../backups/BackupHistory";
// Rassemble les tables, les sauvegardes et les actions de la base MySQL choisie.
// database indique la base à explorer. Les états gardent ses tables et la table sélectionnée. Les fonctions
// transmises aux enfants permettent de recharger les listes après leurs actions.
function DatabaseExplorer({ database, onDeleted, onRestored, onCopyBusy }) {
  const [copying, setCopying] = useState(false);
  const [tables, setTables] = useState([]);
  const [selectedTable, setSelectedTable] = useState("");
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");
  const [backupsVersion, setBackupsVersion] = useState(0);
  // Charge les tables de la base et retire la sélection si la table n’existe plus.
  // L’adresse contient la base sélectionnée. Les tables reçues remplacent la liste affichée. Si la table
  // sélectionnée a disparu, sa sélection est effacée. Une erreur est affichée sans bloquer définitivement le
  // chargement.
  async function loadTables() {
    setLoading(true);
    setError("");
    try {
      const data = await request(`${databasePath(database)}/tables`);
      setTables(data.tables);
      // Retire la sélection si la table n’existe plus.
      setSelectedTable((previous) =>
        data.tables.includes(previous) ? previous : "",
      );
    } catch (error) {
      setError(error.message);
    } finally {
      setLoading(false);
    }
  }
  // Relance le chargement quand les valeurs entre crochets changent.
  useEffect(() => {
    loadTables();
  }, [database]);
  // Met à jour la sélection après une modification de table puis recharge la liste.
  // Le serveur peut renvoyer un nouveau nom après un renommage. Ce nom devient la sélection, le message est
  // affiché et loadTables() récupère la liste à jour.
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
      <div className="database-toolbar" aria-label="Actions de la base">
      <fieldset className="database-copy-scope" disabled={copying}>
      <CreateBackup
        database={database}
        onCreated={() => setBackupsVersion((value) => value + 1)}
      />
      </fieldset>
      <CopyDatabaseToPostgres database={database} onBusy={(busy) => { setCopying(busy); onCopyBusy(busy); }} />
      </div>
      <fieldset className="database-copy-scope" disabled={copying}>
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
      <BackupHistory
        key={database}
        database={database}
        refreshKey={backupsVersion}
        onRestored={onRestored}
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
      </fieldset>
    </>
  );
}

export default DatabaseExplorer;
