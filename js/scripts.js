// Arquivo vazio ou pode ser removido se não estiver sendo usado

// Funções para o modal
function editarTransacao(transacao) {
    const modal = document.getElementById('modalEdicao');
    
    // Preencher os campos do formulário
    document.getElementById('edit-id').value = transacao.id;
    document.getElementById('edit-descricao').value = transacao.descricao;
    document.getElementById('edit-valor').value = transacao.valor;
    document.getElementById('edit-tipo').value = transacao.tipo;
    document.getElementById('edit-data').value = transacao.data;
    
    // Exibir o modal
    modal.style.display = 'block';
}

// Fechar o modal quando clicar no X
document.querySelector('.close').onclick = function() {
    document.getElementById('modalEdicao').style.display = 'none';
}

// Fechar o modal quando clicar fora dele
window.onclick = function(event) {
    const modal = document.getElementById('modalEdicao');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}
