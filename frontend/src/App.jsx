import { useRef, useState } from "react";
import ConnectionForm from "./components/connections/ConnectionForm";
import MysqlView from "./components/views/MysqlView";
import PostgresBackups from "./components/views/PostgresBackups";
import AlertsPanel from "./components/alerts/AlertsPanel";
import useAlerts from "./components/alerts/useAlerts";

const views = [
  { id: "connections", label: "Connexions", Component: null },
  { id: "mysql", label: "MySQL", Component: MysqlView },
  { id: "postgres", label: "PostgreSQL", Component: PostgresBackups },
];
// Affiche le menu et garde en mémoire la vue ouverte pour retrouver ses sélections.
// useState() garde la vue active et les vues déjà ouvertes. Les boutons du menu changent uniquement la vue
// affichée. Les composants masqués restent montés pour conserver les sélections.
export default function App() {
  const alerts = useAlerts();
  const [alertsOpen, setAlertsOpen] = useState(false);
  const alertsButton = useRef(null);
  const [mysqlMenu, setMysqlMenu] = useState(null);
  const [connectionOpen, setConnectionOpen] = useState(false);
  const [active, setActive] = useState("connections");
  // Les vues déjà ouvertes restent montées : leurs sélections ne sont pas perdues.
  const [visited, setVisited] = useState(["connections"]);
  // Change la vue visible sans effacer les vues déjà ouvertes.
  // L’identifiant reçu devient la vue active. Il est aussi ajouté aux vues déjà visitées pour conserver leurs
  // composants et leurs sélections quand on revient dessus.
  function navigate(id) {
    setActive(id);
    setVisited((current) => current.includes(id) ? current : [...current, id]);
  }
  return (
    <div className="workspace">
      <aside className="sidebar">
        <div className="brand">
          <span className="eyebrow">L’atelier des données</span>
          <h1>Mini<br />phpMyAdmin<span>.</span></h1>
          <p>par Théoa</p>
        </div>
        <nav aria-label="Vues SafeBase" className="view-navigation">
          {views.map(({ id, label }) => (
            <button key={id} type="button" id={`nav-${id}`}
              className={`database-button${active === id ? " is-active" : ""}`}
              aria-current={active === id ? "page" : undefined}
              aria-controls={`view-${id}`} onClick={() => navigate(id)}>
              <span className="database-name">{label}</span>
              <span aria-hidden="true">↗</span>
            </button>
          ))}
        </nav>
        <div ref={setMysqlMenu} hidden={active !== "mysql"} />
        <div className="sidebar-footer">
          <span className="footer-line" />
          <p>Explorer. Comprendre. Construire.</p>
          <span>React · FlightPHP · MySQL · PostgreSQL</span>
        </div>
      </aside>
      <main className="main-content">
        <header className="topbar">
          <span>ESPACE DE TRAVAIL / {views.find((view) => view.id === active).label}</span>
          <div className="topbar-actions">
            <span className="environment">Développement local</span>
            <button ref={alertsButton} type="button" className="action-button"
              aria-haspopup="dialog" aria-controls="alerts-dialog" aria-expanded={alertsOpen}
              title={alerts.alerts === null ? "Historique des alertes : nombre indisponible" : "Historique des alertes disponibles"}
              onClick={() => setAlertsOpen(true)}>
              Alertes · {alerts.alerts === null ? "—" : alerts.alerts.length}
            </button>
          </div>
        </header>
        {views.map(({ id, Component }) => (
          <div key={id} id={`view-${id}`} className="workspace-view"
            role="region" aria-labelledby={`nav-${id}`} hidden={active !== id}>
            {id === "connections" ? (
              <section className="explorer" aria-labelledby="connections-title">
                <div className="section-heading">
                  <div>
                    <p className="eyebrow">SafeBase / Connexions</p>
                    <h2 id="connections-title">Connexions</h2>
                    <p className="section-description">
                      Testez l’accès à votre serveur. L’enregistrement des connexions n’est pas encore disponible.
                    </p>
                  </div>
                </div>
                <button type="button" className="action-button" aria-expanded={connectionOpen}
                  aria-controls="connection-form-panel" onClick={() => setConnectionOpen((open) => !open)}>
                  {connectionOpen ? "Fermer le formulaire" : "Ajouter une connexion"}
                </button>
                <div id="connection-form-panel" hidden={!connectionOpen}>
                  {connectionOpen && <ConnectionForm />}
                </div>
              </section>
            ) : visited.includes(id) && <Component menuTarget={mysqlMenu} />}
          </div>
        ))}
        <AlertsPanel open={alertsOpen} onClose={() => setAlertsOpen(false)}
          triggerRef={alertsButton} alerts={alerts} />
        <footer className="main-footer">
          <span>MINI PHPMYADMIN</span><span>Une création Théoa</span>
        </footer>
      </main>
    </div>
  );
}
