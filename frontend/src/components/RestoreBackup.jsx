import { useId, useState } from "react";
import { request, databasePath } from "../services/api";
import Feedback from "./Feedback";
// Ouvre un formulaire pour restaurer le fichier choisi dans une nouvelle base.
// database et filename désignent la sauvegarde choisie. open masque le formulaire au départ. target garde le nom
// de destination et onRestored prévient le parent après une restauration réussie.
function RestoreBackup({ database, filename, onRestored, engine = "mysql" }) {
  const id = useId();
  const [open, setOpen] = useState(false);
  const [target, setTarget] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");
  // Envoie le fichier choisi et le nom de la nouvelle base au moteur concerné.
  // Le fichier vient de l’historique et target contient le nom de la nouvelle base. La route POST dépend du
  // moteur. Après réussite, le nom est vidé et onRestored() avertit le parent pour recharger les bases.
  async function handleSubmit(event) {
    event.preventDefault();
    // Ignore un deuxième clic pendant que le serveur traite déjà la restauration.
    if (loading) return;

    setLoading(true);
    setError("");
    setSuccess("");

    try {
      // Chaque moteur utilise ses propres routes.
      const basePath =
        engine === "postgres"
          ? `/api/postgres/databases/${encodeURIComponent(database)}`
          : databasePath(database);

      const backupName = encodeURIComponent(filename.slice(0, -4));

      const data = await request(`${basePath}/backups/${backupName}/restore`, {
        method: "POST",
        body: { target: target.trim() },
      });

      setSuccess(`${data.message} : ${data.database}`);
      setTarget("");
      onRestored?.(data);
    } catch (error) {
      setError(error.message);
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="disclosure">
      <button type="button" className="action-button" aria-expanded={open}
        aria-controls={id} onClick={() => setOpen((value) => !value)}>
        {open ? "Fermer la restauration" : "Restaurer"}
      </button>
      <div id={id} hidden={!open}>
    <form className="restore-backup" onSubmit={handleSubmit}>
      <label>
        Restaurer dans une nouvelle base
        <input
          type="text"
          value={target}
          onChange={(event) => setTarget(event.target.value)}
          placeholder={
            engine === "postgres"
              ? "Ex. : safebase_test"
              : "Ex. : atelier_theoa_test"
          }
          pattern={
            engine === "postgres"
              ? "[a-zA-Z][a-zA-Z0-9_]{0,62}"
              : "[a-zA-Z][a-zA-Z0-9_]{0,63}"
          }
          maxLength={engine === "postgres" ? 63 : 64}
          disabled={loading}
          required
        />
      </label>

      <button type="submit" disabled={loading}>
        {loading ? "Restauration en cours…" : "Restaurer"}
      </button>

      <Feedback error={error} success={success} />
    </form>
      </div>
    </div>
  );
}

export default RestoreBackup;
