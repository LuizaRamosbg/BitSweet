<?php
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/AlertaEstoque.php';

class AlertaEstoqueController extends Controller {
    private $alerta;

    public function __construct($db) {
        parent::__construct($db);
        $this->alerta = new AlertaEstoque($db);
    }

    public function handle(): void {
        $this->dispatch([
            'GET' => 'handleGet',
            'POST' => 'handlePost',
        ]);
    }

    protected function handleGet(): void {
        if ($this->getQuery('verificar_alertas')) {
            $alertas = $this->alerta->obterAlertasAtuais();
            $this->sendSuccess(['data' => ['alertas' => $alertas, 'alertas_gerados' => count($alertas)]], "Verificação concluída. " . count($alertas) . " alertas detectados.");
        }

        if ($this->getQuery('nao_visualizados')) {
            $alertas = $this->alerta->obterAlertasAtuais();
            $this->sendSuccess(['data' => $alertas]);
        }

        if ($this->getQuery('todos')) {
            $limite = (int) ($this->getQuery('limite') ?? 50);
            $stmt = $this->alerta->listarTodosAlertas($limite);
            $this->sendSuccess(['data' => $this->fetchAll($stmt)]);
        }

        if ($this->getQuery('estatisticas')) {
            $this->sendSuccess(['data' => $this->alerta->obterEstatisticasAtuais()]);
        }

        if ($this->getQuery('por_periodo')) {
            $data_inicio = $this->getQuery('data_inicio') ?? date('Y-m-01');
            $data_fim = $this->getQuery('data_fim') ?? date('Y-m-d');
            $this->sendSuccess(['data' => $this->alerta->obterAlertasPorPeriodo($data_inicio, $data_fim)]);
        }

        if ($this->getQuery('insumos_criticos')) {
            $percentual_minimo = (float) ($this->getQuery('percentual_minimo') ?? 0.1);
            $this->sendSuccess(['data' => $this->alerta->obterInsumosCriticos($percentual_minimo)]);
        }

        $this->sendError('Parâmetros inválidos', 400);
    }

    protected function handlePost(): void {
        if (isset($this->input['marcar_visualizado'])) {
            $alerta_id = (int) ($this->input['alerta_id'] ?? 0);
            if ($alerta_id <= 0) {
                $this->sendError('ID do alerta não fornecido', 400);
            }
            if ($this->alerta->marcarComoVisualizado($alerta_id)) {
                $this->sendSuccess([], 'Alerta marcado como visualizado');
            }
            $this->sendError('Erro ao marcar alerta como visualizado', 500);
        }

        if (isset($this->input['marcar_todos_visualizados'])) {
            if ($this->alerta->marcarTodosComoVisualizados()) {
                $this->sendSuccess([], 'Todos os alertas foram marcados como visualizados');
            }
            $this->sendError('Erro ao marcar alertas como visualizados', 500);
        }

        if (isset($this->input['enviar_notificacao'])) {
            $alerta_id = (int) ($this->input['alerta_id'] ?? 0);
            if ($alerta_id <= 0) {
                $this->sendError('ID do alerta não fornecido', 400);
            }
            if ($this->alerta->enviarNotificacaoEmail($alerta_id)) {
                $this->sendSuccess([], 'Notificação enviada com sucesso');
            }
            $this->sendError('Erro ao enviar notificação', 500);
        }

        if (isset($this->input['limpar_antigos'])) {
            if ($this->alerta->limparAlertasAntigos()) {
                $this->sendSuccess([], 'Alertas antigos foram removidos');
            }
            $this->sendError('Erro ao limpar alertas antigos', 500);
        }

        $this->sendError('Dados inválidos', 400);
    }
}
