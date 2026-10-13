import fs from 'node:fs';
import path from 'node:path';

import type { Plugin } from 'vite';

/**
 * Troca a marca do upstream nas traducoes que o laravel-vue-i18n gera.
 *
 * Por que um plugin e nao edicao dos arquivos de idioma: sao 46 chaves com
 * "TryPost" em CADA um dos 16 idiomas — 736 strings. Editar tudo colocaria 16
 * diretorios do upstream no nosso diff e geraria conflito em toda release.
 *
 * O plugin `i18n` do laravel-vue-i18n gera `lang/php_<locale>.json` no
 * buildStart (e apaga no buildEnd). Este roda depois dele, com enforce:'post',
 * e reescreve o JSON ja gerado. Os arquivos de idioma do upstream ficam
 * intocados e a troca acompanha sozinha qualquer string nova que eles criem.
 *
 * IMPORTANTE: `auth.reviews.*` fica de fora. Sao depoimentos de pessoas e
 * empresas REAIS elogiando o TryPost, mais um selo de nota do G2. Trocar a
 * marca ali criaria endosso falso atribuido a gente identificavel e uma
 * avaliacao que a MidiaMax nao tem. O painel que exibia esses depoimentos foi
 * removido das telas de login e cadastro — a exclusao aqui e a segunda trava,
 * para o caso de alguem reativar o painel.
 */
export default function rebrandI18n(brand: string): Plugin {
    const EXCLUIDAS = /^auth\.reviews\./;
    const ALVO = /TryPost/g;

    const reescrever = (): void => {
        const dir = path.resolve('lang');

        if (!fs.existsSync(dir)) {
            return;
        }

        for (const arquivo of fs.readdirSync(dir)) {
            if (!/^php_.+\.json$/.test(arquivo)) {
                continue;
            }

            const caminho = path.join(dir, arquivo);
            let linhas: Record<string, unknown>;

            try {
                linhas = JSON.parse(fs.readFileSync(caminho, 'utf-8'));
            } catch {
                continue; // arquivo sendo escrito agora; o proximo ciclo pega
            }

            let mudou = false;

            for (const [chave, valor] of Object.entries(linhas)) {
                if (typeof valor !== 'string' || EXCLUIDAS.test(chave)) {
                    continue;
                }

                const novo = valor.replace(ALVO, brand);

                if (novo !== valor) {
                    linhas[chave] = novo;
                    mudou = true;
                }
            }

            if (mudou) {
                fs.writeFileSync(caminho, JSON.stringify(linhas));
            }
        }
    };

    return {
        name: 'midiamax-rebrand-i18n',
        enforce: 'post',
        buildStart: reescrever,
        configureServer: reescrever,
    };
}
