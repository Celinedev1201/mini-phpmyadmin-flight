import { useEffect, useState } from "react";
import CreateDatabase from "./components/CreateDatabase";
import DatabaseExplorer from "./components/DatabaseExplorer";
import Feedback from "./components/Feedback";
import { request } from "./services/api";

function App() {
  const [databases, setDatabases] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [selectedDatabase, setSelectedDatabase] = useState("");
  const [success, setSuccess] = useState("");

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

  useEffect(() => {
    loadDatabases();
  }, []);

  function selectDatabase(database) {
    setSelectedDatabase(database);
    setSuccess("");
  }

  function databaseDeleted(data) {
    setSelectedDatabase("");
    setSuccess(data.message);
    loadDatabases();
  }

  return (
    <div className="workspace">
      <aside className="sidebar">
        <div className="brand">
          <span className="eyebrow">L’atelier des données</span>
          <h1>
            Mini
            <br />
            phpMyAdmin<span>.</span>
          </h1>
          <p>par Théoa</p>
        </div>
        <CreateDatabase onCreated={loadDatabases} />
        <nav aria-label="Bases de données">
          <div className="nav-heading">
            <span>Bases de données</span>
            <span className="count">{databases.length}</span>
          </div>
          <Feedback loading={loading} error={error} />
          {error && (
            <button className="database-button" onClick={loadDatabases}>
              Réessayer
            </button>
          )}
          <ul className="database-list">
            {databases.map((database, index) => (
              <li key={database}>
                <button
                  className={
                    selectedDatabase === database
                      ? "database-button is-active"
                      : "database-button"
                  }
                  aria-current={
                    selectedDatabase === database ? "true" : undefined
                  }
                  onClick={() => selectDatabase(database)}
                >
                  <span className="database-number">
                    {String(index + 1).padStart(2, "0")}
                  </span>
                  <span className="database-name">{database}</span>
                  <span aria-hidden="true">↗</span>
                </button>
              </li>
            ))}
          </ul>
        </nav>
        <div className="sidebar-footer">
          <span className="footer-line" />
          <p>Explorer. Comprendre. Construire.</p>
          <span>React · FlightPHP · MySQL</span>
        </div>
      </aside>
      <main className="main-content">
        <header className="topbar">
          <span>ESPACE DE TRAVAIL</span>
          <span className="environment">Développement local</span>
        </header>
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
                <span className="welcome-symbol" aria-hidden="true">
                  ✧
                </span>
                <p>
                  Chaque projet commence
                  <br />
                  par une <em>structure.</em>
                </p>
                <span>Choisissez votre base dans le menu.</span>
              </div>
            </>
          )}
        </section>
        <footer className="main-footer">
          <span>MINI PHPMYADMIN</span>
          <span>Une création Théoa</span>
        </footer>
      </main>
    </div>
  );
}

export default App;
