import { useEffect, useState } from "react";
import { request } from "../services/api";
import DatabaseExplorer from "./DatabaseExplorer";
import CreateDatabase from "./CreateDatabase";
import Feedback from "./Feedback";
// Garde la base sélectionnée et affiche le menu et l’explorateur MySQL.
export default function MysqlView() {
  const [databases, setDatabases] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [selectedDatabase, setSelectedDatabase] = useState("");
  const [success, setSuccess] = useState("");
  // Récupère les bases auprès du serveur et met à jour la liste affichée.
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
  function selectDatabase(database) {
    setSelectedDatabase(database);
    setSuccess("");
  }
  // Retire la base sélectionnée de l’écran et recharge les bases après la suppression.
  function databaseDeleted(data) {
    setSelectedDatabase("");
    setSuccess(data.message);
    loadDatabases();
  }
  return (
    <div className="mysql-layout">
      <aside className="mysql-menu" aria-label="Gestion MySQL">
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
      </aside>
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
