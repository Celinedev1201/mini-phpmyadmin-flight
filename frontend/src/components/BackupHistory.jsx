import { useEffect, useState } from "react";
import { request, databasePath } from "../services/api";
import Feedback from "./Feedback";
import RestoreBackup from "./RestoreBackup";
// Affiche les sauvegardes de la base avec les actions de téléchargement et de restauration.
function BackupHistory({ database, refreshKey, onRestored, engine = "mysql" }) {
  const [backups, setBackups] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  // Chaque moteur possède ses propres routes de sauvegarde.
  const path =
    engine === "postgres"
      ? `/api/postgres/databases/${encodeURIComponent(database)}/backups`
      : `${databasePath(database)}/backups`;
  // Relance le chargement quand les valeurs entre crochets changent.
  useEffect(() => {
    const controller = new AbortController();
    // Récupère l’historique des sauvegardes et ignore la réponse si la demande a été annulée.
    async function loadBackups() {
      setLoading(true);
      setError("");
      setBackups([]);

      try {
        const data = await request(path, { signal: controller.signal });

        if (!controller.signal.aborted) {
          setBackups(data.backups);
        }
      } catch (error) {
        if (!controller.signal.aborted) setError(error.message);
      } finally {
        if (!controller.signal.aborted) setLoading(false);
      }
    }

    loadBackups();
    return () => controller.abort();
  }, [path, refreshKey]);

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
                <th scope="col">Actions</th>
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
                      href={`${path}/${encodeURIComponent(backup.filename.slice(0, -4))}/download`}
                    >
                      Télécharger
                    </a>
                    <RestoreBackup
                      database={database}
                      filename={backup.filename}
                      engine={engine}
                      onRestored={onRestored}
                    />
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
