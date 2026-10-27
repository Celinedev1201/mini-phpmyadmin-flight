import { useEffect, useState } from "react";
import { createPortal } from "react-dom";
import { request } from "../../services/api";
import DatabaseExplorer from "../databases/DatabaseExplorer";
import CreateDatabase from "../databases/CreateDatabase";
import Feedback from "../common/Feedback";
// Garde la base sélectionnée et affiche le menu et l’explorateur MySQL.
// Les états gardent la liste des bases et le nom sélectionné. createPortal() place le menu des bases dans la
// barre de gauche tout en gardant sa logique dans ce composant.
export default function MysqlView({ menuTarget }) {
  const [databases, setDatabases] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [selectedDatabase, setSelectedDatabase] = useState("");
  const [success, setSuccess] = useState("");
  // Récupère les bases auprès du serveur et met à jour la liste affichée.
  // La demande GET lit la liste depuis l’API. setDatabases() garde les noms reçus. Le catch garde le message
  // d’erreur et le finally termine le chargement, même si la demande échoue.
  async function loadDatabases() {
    setLoading(true);
    setError("");
    try {
      const data = await request("/api/databases");
      setDatabases(data.databases);
    } catch (error) {
      setError(error.message);
    } finally {
      setLoading(false);
    }
  }
  // Charge les bases à la première ouverture de la vue MySQL.
  useEffect(() => {
    loadDatabases();
  }, []);
  // Mémorise la base choisie et efface le message de réussite précédent.
  // Le nom reçu est mémorisé dans selectedDatabase. React affiche alors l’explorateur de cette base. L’ancien
  // message de réussite est effacé pour éviter de le confondre avec la nouvelle sélection.
  function selectDatabase(database) {
    setSelectedDatabase(database);
    setSuccess("");
  }
  // Retire la base sélectionnée de l’écran et recharge les bases après la suppression.
  // Cette fonction est appelée après une suppression réussie. Elle retire la sélection, affiche le message du
  // serveur et demande une nouvelle liste des bases.
  function databaseDeleted(data) {
    setSelectedDatabase("");
    setSuccess(data.message);
    loadDatabases();
  }
  return (
    <div className="mysql-layout">
      {menuTarget && createPortal(
      <div className="mysql-menu">
        <CreateDatabase onCreated={loadDatabases} />
        <nav aria-label="Bases de données">
          <div className="nav-heading">
            <span>Bases de données</span><span className="count">{databases.length}</span>
          </div>
          <Feedback loading={loading} error={error} />
          {error && <button className="database-button" onClick={loadDatabases}>Réessayer</button>}
          <ul className="database-list">
            {databases.map((database, index) => (
              <li key={database}>
                <button className={`database-button${selectedDatabase === database ? " is-active" : ""}`}
                  aria-current={selectedDatabase === database ? "true" : undefined}
                  onClick={() => selectDatabase(database)}>
                  <span className="database-number">{String(index + 1).padStart(2, "0")}</span>
                  <span className="database-name">{database}</span><span aria-hidden="true">↗</span>
                </button>
              </li>
            ))}
          </ul>
        </nav>
      </div>, menuTarget)}
        <section className="explorer" aria-labelledby="explorer-title">
          <Feedback success={success} />
          {selectedDatabase ? (
            <DatabaseExplorer
              key={selectedDatabase}
              database={selectedDatabase}
              onDeleted={databaseDeleted}
              onRestored={loadDatabases}
            />
          ) : (
            <>
              <div className="section-heading">
                <div>
                  <p className="eyebrow">Explorateur / MySQL</p>
                  <h2 id="explorer-title">Vos données, autrement.</h2>
                  <p className="section-description">
                    Sélectionnez une base pour explorer sa structure.
                  </p>
                </div>
              </div>
              <div className="welcome">
                <span className="welcome-symbol" aria-hidden="true">✧</span>
                <p>Chaque projet commence<br />par une <em>structure.</em></p>
                <span>Choisissez votre base dans le menu.</span>
              </div>
            </>
          )}
        </section>
    </div>
  );
}
