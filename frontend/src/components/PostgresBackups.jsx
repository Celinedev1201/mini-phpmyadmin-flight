import { useEffect, useState } from "react";
import { request } from "../services/api";
import Feedback from "./Feedback";
import BackupHistory from "./BackupHistory";
import PostgresBackupForm from "./PostgresBackupForm";

function PostgresBackups() {
  const [databases, setDatabases] = useState([]);
  const [database, setDatabase] = useState("");
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [backupsVersion, setBackupsVersion] = useState(0);
  const [databasesVersion, setDatabasesVersion] = useState(0);

  useEffect(() => {
    const controller = new AbortController();
    setLoading(true);
    setError("");

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
        onSelect={setDatabase}
        onSaved={() => setBackupsVersion((value) => value + 1)}
      />
      <button className="action-button" type="button" disabled={loading}
        onClick={() => setDatabasesVersion((value) => value + 1)}>
        Actualiser les bases
      </button>
      <Feedback loading={loading} error={error} />
      {database && (
        <BackupHistory
          key={database}
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
