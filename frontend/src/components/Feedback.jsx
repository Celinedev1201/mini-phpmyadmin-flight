export default function Feedback({ loading, pending, error, success }) {
  return <>
    {(loading || pending) && <p className="feedback" role="status">Chargement en cours…</p>}
    {error && <p className="feedback feedback-error" role="alert">{error}</p>}
    {success && <p className="feedback feedback-success" role="status">{success}</p>}
  </>;
}
