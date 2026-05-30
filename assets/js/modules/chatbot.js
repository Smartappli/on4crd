(function () {
  const chatbotForm = document.getElementById('chatbot-form');
  const chatbotInput = document.getElementById('chatbot-question');
  const chatbotThread = document.getElementById('chatbot-thread');
  if (chatbotThread) {
    chatbotThread.scrollTop = chatbotThread.scrollHeight;
  }
  if (chatbotForm && chatbotInput) {
    const chatbotCounter = document.getElementById('chatbot-counter');
    const updateChatbotCounter = () => {
      if (chatbotCounter) chatbotCounter.textContent = String(chatbotInput.value.length);
    };
    updateChatbotCounter();
    chatbotInput.addEventListener('input', updateChatbotCounter);
    chatbotInput.addEventListener('keydown', (event) => {
      if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        chatbotForm.submit();
      }
    });
    document.querySelectorAll('[data-suggestion]').forEach((button) => {
      button.addEventListener('click', () => {
        const value = button.getAttribute('data-suggestion') || '';
        chatbotInput.value = value;
        updateChatbotCounter();
        chatbotInput.focus();
      });
    });
    document.querySelectorAll('[data-copy-target]').forEach((button) => {
      button.addEventListener('click', async () => {
        const target = document.getElementById(button.getAttribute('data-copy-target') || '');
        const text = target ? target.textContent || '' : '';
        if (!text || !navigator.clipboard) return;
        await navigator.clipboard.writeText(text);
        button.classList.add('is-copied');
        window.setTimeout(() => button.classList.remove('is-copied'), 1200);
      });
    });
  }

})();

