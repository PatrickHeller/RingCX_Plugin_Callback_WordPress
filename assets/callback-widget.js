document.addEventListener('DOMContentLoaded', () => {
    const widget = document.getElementById('callback4ringcx-widget');
    const trigger = document.getElementById('callback4ringcx-trigger');
    const modal = document.getElementById('callback4ringcx-modal');
    const closeButton = document.getElementById('callback4ringcx-close');
    const form = document.getElementById('callback4ringcx-form');
    const status = document.getElementById('callback4ringcx-status');
    const agentSelect = document.getElementById('callback4ringcx-agent-id');
    const agentNameInput = document.getElementById('callback4ringcx-agent-name');

    if (!widget || !trigger || !modal || !closeButton || !form || !status || !window.callback4ringcxData) {
        return;
    }

    const setStatus = (message, type = '') => {
        status.textContent = message || '';
        status.className = 'callback4ringcx-status';

        if (type) {
            status.classList.add(`is-${type}`);
        }
    };

    const openModal = () => {
        modal.hidden = false;
        trigger.setAttribute('aria-expanded', 'true');
    };

    const closeModal = () => {
        modal.hidden = true;
        trigger.setAttribute('aria-expanded', 'false');
    };

    const loadAgents = async () => {
        if (!agentSelect) {
            return;
        }

        agentSelect.innerHTML = '<option value="">Agenten werden geladen...</option>';

        const formData = new FormData();
        formData.append('action', callback4ringcxData.loadAgentsAction);
        formData.append('nonce', callback4ringcxData.nonce);

        try {
            const response = await fetch(callback4ringcxData.ajaxUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });

            const result = await response.json();

            if (!result.success || !result.data || !Array.isArray(result.data.agents)) {
                agentSelect.innerHTML = '<option value="">Keine Agenten verfügbar</option>';
                return;
            }

            agentSelect.innerHTML = '<option value="">Bitte auswählen</option>';

            result.data.agents.forEach((agent) => {
                const option = document.createElement('option');
                option.value = agent.id;
                option.textContent = agent.name;
                option.dataset.agentName = agent.name;
                agentSelect.appendChild(option);
            });
        } catch (error) {
            agentSelect.innerHTML = '<option value="">Fehler beim Laden</option>';
        }
    };

    trigger.addEventListener('click', () => {
        openModal();

        if (agentSelect && agentSelect.options.length <= 1) {
            loadAgents();
        }
    });

    closeButton.addEventListener('click', () => {
        closeModal();
    });

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.hidden) {
            closeModal();
        }
    });

    if (agentSelect && agentNameInput) {
        agentSelect.addEventListener('change', () => {
            const selectedOption = agentSelect.options[agentSelect.selectedIndex];
            agentNameInput.value = selectedOption ? (selectedOption.dataset.agentName || '') : '';
        });
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        setStatus('Bitte warten...', 'loading');

        const formData = new FormData(form);
        formData.append('action', callback4ringcxData.submitAction);
                          formData.append('nonce', callback4ringcxData.nonce);

                          try {
                              const response = await fetch(callback4ringcxData.ajaxUrl, {
                                  method: 'POST',
                                  body: formData,
                                  credentials: 'same-origin'
                              });

                              const result = await response.json();

                              if (!result.success) {
                                  const message = result.data && result.data.message
                                  ? result.data.message
                                  : 'Die Anfrage konnte nicht verarbeitet werden.';
                                  setStatus(message, 'error');
                                  return;
                              }

                              const message = result.data && result.data.message
                              ? result.data.message
                              : callback4ringcxData.successMessage;

                              setStatus(message, 'success');
                              form.reset();

                          if (agentNameInput) {
                              agentNameInput.value = '';
                          }
                          } catch (error) {
                              setStatus('Es ist ein technischer Fehler aufgetreten.', 'error');
                          }
    });
});
