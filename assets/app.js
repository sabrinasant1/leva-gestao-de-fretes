// Máscaras simples (CEP, CNPJ, telefone) - sem validação de autenticidade
document.querySelectorAll('[data-mask]').forEach(el => el.addEventListener('input', () => {
  let v = el.value.replace(/\D/g, '');
  if (el.dataset.mask === 'cep') v = v.slice(0, 8).replace(/(\d{5})(\d)/, '$1-$2');
  if (el.dataset.mask === 'cnpj') v = v.slice(0, 14).replace(/(\d{2})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1/$2').replace(/(\d{4})(\d)/, '$1-$2');
  if (el.dataset.mask === 'tel') v = v.slice(0, 11).replace(/(\d{2})(\d)/, '($1) $2').replace(/(\d{5})(\d)/, '$1-$2');
  el.value = v;
}));

// Tela "Novo frete": linhas de produtos e resumo
const itens = document.getElementById('itens');
if (itens) {
  const fmt = n => n.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' kg';
  const txt = id => { const s = document.getElementById(id); return s.value ? s.options[s.selectedIndex].text : '—'; };
  function addLinha() {
    const tr = document.createElement('tr');
    tr.innerHTML = `<td><select name="produto_id[]" required><option value="">Selecione</option>${PRODUTOS.map(p => `<option value="${p.id}">${p.nome} (${p.unidade})</option>`).join('')}</select></td>
      <td><input type="number" name="quantidade[]" min="1" value="1" class="qt" required></td><td class="pt">0,00 kg</td>
      <td><button type="button" class="x">×</button></td>`;
    tr.querySelector('.x').onclick = () => { tr.remove(); resumo(); };
    itens.appendChild(tr);
  }
  function resumo() {
    let peso = 0, qtd = 0;
    itens.querySelectorAll('tr').forEach(tr => {
      const p = PRODUTOS.find(x => x.id == tr.querySelector('select').value), q = +tr.querySelector('.qt').value || 0;
      const t = p ? p.peso * q : 0; tr.querySelector('.pt').textContent = fmt(t);
      if (p) { peso += t; qtd++; }
    });
    rT.textContent = txt('transp'); rO.textContent = txt('orig'); rD.textContent = txt('dest');
    rDt.textContent = data.value ? data.value.split('-').reverse().join('/') : '—'; rP.textContent = qtd; rW.textContent = fmt(peso);
  }
  document.getElementById('addProd').onclick = () => { addLinha(); resumo(); };
  document.getElementById('frete').addEventListener('input', resumo);
  document.getElementById('frete').addEventListener('change', resumo);
  addLinha(); resumo();
}
