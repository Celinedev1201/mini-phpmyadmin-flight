import { useState } from "react";
import { request } from "../../services/api";
import Feedback from "./Feedback";

// Une question sous le bouton suffit pour confirmer la suppression.
// Demande une confirmation avant de supprimer la ressource reçue dans les paramètres.
// path désigne la ressource à supprimer. explicitName exige de recopier le nom pour une base. Le premier clic
// affiche seulement la confirmation ; la suppression part après validation.
function DeleteButton({ path, target, explicitName = false, body = {}, onDeleted, disabled }) {
  const [confirming, setConfirming] = useState(false);
  const [name, setName] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  // Ouvre la confirmation avec un champ et un message d’erreur remis à zéro.
  // Le nom recopié et l’ancienne erreur sont vidés. confirming devient true pour afficher la question. Aucun appel
  // de suppression n’est encore envoyé.
  function askConfirmation() {
    setName("");
    setError("");
    setConfirming(true);
  }
  // Envoie la suppression confirmée, avec le nom recopié quand il est obligatoire.
  // La fonction attend la confirmation et le nom exact quand il est demandé. Elle envoie ensuite DELETE à
  // l’adresse reçue. En cas de réussite, onDeleted() prévient le parent ; en cas d’erreur, la confirmation reste
  // affichée.
  async function handleDelete() {
    if (loading || (explicitName && name !== target)) return;
    setLoading(true);
    setError("");
    const confirmation = { ...body, confirmed: true };
    if (explicitName) confirmation.name = name;
    try {
      const data = await request(path, { method: "DELETE", body: confirmation });
      setConfirming(false);
      onDeleted(data);
    } catch (error) {
      setError(error.message);
    } finally {
      setLoading(false);
    }
  }

  return (
    <>
      <button type="button" className="action-button" disabled={disabled || loading}
        onClick={askConfirmation}>Supprimer</button>
      {confirming && (
        <div className="delete-confirmation">
          <p>Supprimer {target} ? Cette action est irréversible.</p>
          {explicitName && (
            <label>Recopiez exactement le nom : {target}
              <input value={name} onChange={(event) => setName(event.target.value)}
                autoComplete="off" autoFocus disabled={loading} />
            </label>
          )}
          <button type="button" className="action-button" onClick={handleDelete}
            disabled={disabled || loading || (explicitName && name !== target)}>
            Supprimer définitivement
          </button>
          <button type="button" className="action-button" disabled={loading}
            onClick={() => setConfirming(false)}>Annuler</button>
          <Feedback loading={loading} error={error} />
        </div>
      )}
    </>
  );
}

export default DeleteButton;
