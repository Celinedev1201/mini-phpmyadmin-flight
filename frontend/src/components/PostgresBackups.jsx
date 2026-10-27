import { useEffect, useState } from "react";
import { request } from "../services/api";
import Feedback from "./Feedback";
import BackupHistory from "./BackupHistory";
import PostgresBackupForm from "./PostgresBackupForm";
import DeleteButton from "./DeleteButton";
// Rassemble les bases PostgreSQL, leurs sauvegardes et les actions disponibles.
// Le composant charge les bases et garde la sélection encore existante. Les compteurs déclenchent une nouvelle
// lecture après une sauvegarde, une restauration ou une suppression.
function PostgresBackups() {
  const [databases, setDatabases] = useState([]);
  const [database, setDatabase] = useState("");
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");
  const [backupsVersion, setBackupsVersion] = useState(0);
  const [databasesVersion, setDatabasesVersion] = useState(0);
  // Relance le chargement quand les valeurs entre crochets changent.
  useEffect(() => {
    const controller = new AbortController();
    setLoading(true);
    setError("");
    // Récupère les bases auprès du serveur et met à jour la liste affichée.
    // La demande GET lit la liste depuis l’API. setDatabases() garde les noms reçus. Le catch garde le message
    // d’erreur et le finally termine le chargement, même si la demande échoue.
    async function loadDatabases() {
      try {
        const data = await request("/api/postgres/databases", {
          signal: controller.signal,
        });
        if (!controller.signal.aborted) {
          setDatabases(data.databases);
          setDatabase((current) => data.databases.includes(current)
            ? current : data.databases[0] || "");
        }
      } catch (error) {
        if (!controller.signal.aborted) setError(error.message);
      } finally {
        if (!controller.signal.aborted) setLoading(false);
      }
    }

    loadDatabases();
    return () => controller.abort();
  }, [databasesVersion]);

  return (
    <section className="table-panel">
      <div className="panel-heading">
        <span>Sauvegardes PostgreSQL</span>
        <span>{databases.length} base(s)</span>
      </div>
      <PostgresBackupForm
        database={database}
        databases={databases}
        loading={loading}
        onSelect={(name) => { setDatabase(name); setSuccess(""); }}
        onSaved={() => setBackupsVersion((value) => value + 1)}
      />
      <button className="action-button" type="button" disabled={loading}
        onClick={() => setDatabasesVersion((value) => value + 1)}>
        Actualiser les bases
      </button>
      {database && (
        <DeleteButton key={`delete-${database}`}
          path={`/api/postgres/databases/${encodeURIComponent(database)}`}
          target={database} explicitName disabled={loading}
          onDeleted={(data) => {
            setDatabases((current) => current.filter((name) => name !== database));
            setDatabase("");
            setSuccess(data.message);
            setDatabasesVersion((value) => value + 1);
          }} />
      )}
      <Feedback loading={loading} error={error} success={success} />
      {database && (
        <BackupHistory
          key={`history-${database}`}
          database={database}
          engine="postgres"
          refreshKey={backupsVersion}
          onRestored={() => setDatabasesVersion((v) => v + 1)}
        />
      )}
    </section>
  );
}
export default PostgresBackups;
