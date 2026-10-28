import { useEffect, useRef } from "react";
import Alerts from "./Alerts";

export default function AlertsPanel({ open, onClose, triggerRef, alerts }) {
  const dialogRef = useRef(null);
  const closeRef = useRef(null);

  useEffect(() => {
    const dialog = dialogRef.current;
    if (open && !dialog.open) {
      dialog.showModal();
      closeRef.current.focus();
    } else if (!open && dialog.open) {
      dialog.close();
    }
  }, [open]);

  function handleClose() {
    // Ignore un ancien événement close si le panneau a déjà été rouvert.
    if (dialogRef.current.open) return;
    onClose();
    triggerRef.current?.focus();
  }

  return (
    <dialog ref={dialogRef} id="alerts-dialog" className="alerts-dialog"
      aria-labelledby="alerts-title" onClose={handleClose}
      onCancel={(event) => {
        event.preventDefault();
        onClose();
      }}>
      <div className="alerts-dialog-actions">
        <button ref={closeRef} type="button" className="action-button"
          onClick={onClose}>Fermer les alertes</button>
      </div>
      <Alerts {...alerts} />
    </dialog>
  );
}
