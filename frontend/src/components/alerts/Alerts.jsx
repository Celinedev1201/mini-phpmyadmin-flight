import Feedback from "../common/Feedback";

function Alerts({ alerts, loading, error, refresh }) {

  return (
    <section className="table-panel" aria-labelledby="alerts-title">
      <div className="panel-heading">
        <h2 id="alerts-title">Alertes</h2>
        <button
          className="action-button"
          type="button"
          disabled={loading}
          onClick={refresh}
        >
          Actualiser
        </button>
      </div>

      <Feedback loading={loading} error={error} />

      {!loading && !error && alerts?.length === 0 && (
        <p className="empty-message">Aucune alerte.</p>
      )}

      <ul className="alerts-list">
        {(alerts ?? []).map((alert) => (
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