/* BitSweet - comportamentos globais das páginas. */
(function () {
    "use strict";

    // ------------------------------------------------ confirmação de ações (data-confirmar="mensagem")
    document.addEventListener("submit", function (ev) {
        const msg = ev.target.dataset && ev.target.dataset.confirmar;
        if (msg && !window.confirm(msg)) ev.preventDefault();
    }, true);

    // ------------------------------------------------ rascunho de formulários (data-rascunho)
    // Guarda no navegador o que está sendo digitado. Ao sair da página e voltar, os campos são
    // preenchidos de novo. O rascunho é apagado quando o formulário é salvo com sucesso; se o
    // servidor recusar (mensagem de erro), os dados continuam lá para corrigir.
    const PREFIXO = "bitsweet:rascunho:";
    const PENDENTE = "bitsweet:rascunho-enviado";
    const LINHA = ".ingrediente-row, .extra-row, .item-encomenda";

    const armazenamento = {
        ler(k) { try { return JSON.parse(localStorage.getItem(k)); } catch (e) { return null; } },
        gravar(k, v) { try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) { /* sem storage */ } },
        apagar(k) { try { localStorage.removeItem(k); } catch (e) { /* sem storage */ } },
    };

    function campos(form) {
        return Array.from(form.elements).filter(function (c) {
            return c.name && !c.disabled && !["submit", "button", "hidden", "password", "file"].includes(c.type);
        });
    }

    function capturar(form) {
        return campos(form).map(function (c) {
            return [c.name, (c.type === "checkbox" || c.type === "radio") ? c.checked : c.value];
        });
    }

    function garantirLinhas(form, nome, total) {
        // listas dinâmicas (insumos da receita, itens da encomenda): recria as linhas que faltam
        let existentes = form.querySelectorAll('[name="' + nome + '"]');
        while (existentes.length < total) {
            const linha = existentes[existentes.length - 1] && existentes[existentes.length - 1].closest(LINHA);
            if (!linha) return;
            const nova = linha.cloneNode(true);
            nova.querySelectorAll("input, select, textarea").forEach(function (c) { c.value = ""; });
            const un = nova.querySelector(".ingredient-unit");
            if (un) un.textContent = "";
            linha.after(nova);
            existentes = form.querySelectorAll('[name="' + nome + '"]');
        }
    }

    function restaurar(form, valores) {
        const totais = {};
        valores.forEach(function (v) { totais[v[0]] = (totais[v[0]] || 0) + 1; });
        Object.keys(totais).forEach(function (n) { garantirLinhas(form, n, totais[n]); });
        const usados = {};
        valores.forEach(function (v) {
            const nome = v[0], i = usados[nome] = (usados[nome] || 0);
            usados[nome]++;
            const c = form.querySelectorAll('[name="' + nome + '"]')[i];
            if (!c) return;
            if (c.type === "checkbox" || c.type === "radio") c.checked = !!v[1];
            else c.value = v[1];
            // dispara os scripts da página (ex.: unidade do insumo, unidades da compra)
            if (c.tagName === "SELECT") c.dispatchEvent(new Event("change", { bubbles: true }));
        });
    }

    function avisar(form, chave) {
        const aviso = document.createElement("div");
        aviso.className = "aviso-rascunho";
        aviso.setAttribute("role", "status");
        aviso.innerHTML = "<span>Recuperamos o que você estava preenchendo neste formulário.</span>";
        const botao = document.createElement("button");
        botao.type = "button";
        botao.className = "btn btn-secundario btn-peq";
        botao.textContent = "Descartar e limpar";
        botao.addEventListener("click", function () { armazenamento.apagar(chave); location.reload(); });
        aviso.appendChild(botao);
        form.parentNode.insertBefore(aviso, form);
    }

    function iniciar() {
        // resultado do último envio: sucesso apaga o rascunho, erro mantém
        let pendente = null;
        try { pendente = sessionStorage.getItem(PENDENTE); sessionStorage.removeItem(PENDENTE); } catch (e) { /* */ }
        if (pendente && !document.querySelector(".flash-error")) armazenamento.apagar(pendente);

        document.querySelectorAll("form[data-rascunho]").forEach(function (form, idx) {
            const chave = PREFIXO + location.pathname + ":" + (form.id || idx);
            const salvo = armazenamento.ler(chave);
            if (salvo && Array.isArray(salvo.valores)) {
                restaurar(form, salvo.valores);
                avisar(form, chave);
            }
            const guardar = function () { armazenamento.gravar(chave, { valores: capturar(form), em: Date.now() }); };
            form.addEventListener("input", guardar);
            form.addEventListener("change", guardar);
            form.addEventListener("click", function (ev) {         // remover linha também altera o rascunho
                if (ev.target.closest(".icon-button")) setTimeout(guardar, 0);
            });
            form.addEventListener("submit", function (ev) {
                if (ev.defaultPrevented) return;
                guardar();
                try { sessionStorage.setItem(PENDENTE, chave); } catch (e) { /* */ }
            });
        });
    }

    if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", iniciar);
    else iniciar();
})();
