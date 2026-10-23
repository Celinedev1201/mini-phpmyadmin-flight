import { useEffect, useState } from "react";
import { request, databasePath } from "../services/api";
import Feedback from "./Feedback";

function BackupHistory({ database, refreshKey }) {
  const [backups, setBackups] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    const controller = new AbortController();

    async function loadBackups() {
      setLoading(true);
      setError("");
      setBackups([]);

      try {
        const data = await request(`${databasePath(database)}/backups`, {
          signal: controller.signal,
        });

        if (!controller.signal.aborted) {
          setBackups(data.backups);
        }
      } catch (error) {
        if (!controller.signal.aborted) {
          setError(error.message);
        }
      } finally {
        if (!controller.signal.aborted) {
          setLoading(false);
        }
      }
    }

    loadBackups();

    // Annule la requête si on change de base ou quitte le composant.
    return () => controller.abort();
  }, [database, refreshKey]);

  return (
    <section className="table-panel">
      <div className="panel-heading">
        <span>Sauvegardes de {database}</span>
        <span>{backups.length}</span>
      </div>

      <Feedback loading={loading} error={error} />

      {!loading && !error && backups.length === 0 && (
        <p className="empty-message">Aucune sauvegarde disponible.</p>
      )}

      {backups.length > 0 && (
        <div className="structure-scroll">
          <table className="structure-table">
            <thead>
              <tr>
                <th scope="col">Fichier</th>
                <th scope="col">Date</th>
                <th scope="col">Taille</th>
                <th scope="col">Action</th>
              </tr>
            </thead>

            <tbody>
              {backups.map((backup) => (
                <tr key={backup.filename}>
                  <td>{backup.filename}</td>
                  <td>{new Date(backup.createdAt).toLocaleString("fr-FR")}</td>
                  <td>{(backup.size / 1024).toFixed(1)} Ko</td>
                  <td>
                    <a
                      className="action-button"
                      href={`${databasePath(database)}/backups/${encodeURIComponent(backup.filename.slice(0, -4))}/download`}
                    >
                      Télécharger
                    </a>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </section>
  );
}

export default BackupHistory;
