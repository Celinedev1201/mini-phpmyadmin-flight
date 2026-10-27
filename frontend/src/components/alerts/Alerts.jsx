import { useEffect, useState } from "react";
import { request } from "../../services/api";
import Feedback from "../common/Feedback";

function Alerts() {
  const [alerts, setAlerts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [version, setVersion] = useState(0);

  useEffect(() => {
    const controller = new AbortController();

    async function loadAlerts() {
      setLoading(true);
      setError("");

      try {
        const data = await request("/api/alerts", {
          signal: controller.signal,
        });

        if (!controller.signal.aborted) {
          setAlerts(data.alerts);
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

    loadAlerts();

    return () => controller.abort();
  }, [version]);

  useEffect(() => {
    const timer = setInterval(() => {
      setVersion((previous) => previous + 1);
    }, 30000);

    return () => clearInterval(timer);
  }, []);

  return (
    <section className="table-panel" aria-labelledby="alerts-title">
      <div className="panel-heading">
        <h2 id="alerts-title">Alertes</h2>
        <button
          className="action-button"
          type="button"
          disabled={loading}
          onClick={() => setVersion((previous) => previous + 1)}
        >
          Actualiser
        </button>
      </div>

      <Feedback loading={loading} error={error} />

      {!loading && !error && alerts.length === 0 && (
        <p className="empty-message">Aucune alerte.</p>
      )}

      <ul className="alerts-list">
        {alerts.map((alert) => (
          <li key={alert.id} className="alert-item">
            <div className="alert-meta">
              <strong>
                {alert.engine === "mysql" ? "MySQL" : "PostgreSQL"}
                {" · "}
                {alert.operation === "backup"
                  ? "Sauvegarde"
                  : "Restauration"}
              </strong>
              <time dateTime={alert.date}>
                {new Date(alert.date).toLocaleString("fr-FR")}
              </time>
            </div>
            <p className="alert-database">{alert.database}</p>
            <p>{alert.message}</p>
          </li>
        ))}
      </ul>
    </section>
  );
}

export default Alerts;