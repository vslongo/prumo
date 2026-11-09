import Box from '@mui/material/Box'
import MuiLink from '@mui/material/Link'
import Typography from '@mui/material/Typography'
import { Link } from 'react-router'
import { formatDate } from '../../../shared/lib/format'
import { Logo } from '../../../shared/ui/Logo'
import { company as c } from '../company'

/*
 * MINUTA. Texto escrito para bater com o que o sistema faz hoje (docs/adr/0008), mas ainda sem revisão de
 * advogado. Não publique em produção antes dessa revisão e de preencher `../company.ts`.
 */

type Section = { title: string; body: string[] }

const terms: Section[] = [
  {
    title: '1. O que é o Prumo',
    body: [
      `O Prumo é um sistema pela internet para oficinas mecânicas: orçamentos, aprovação do orçamento pelo cliente com assinatura, ordens de serviço, cadastro de clientes e veículos e fotos do serviço. É oferecido por ${c.legalName}, CNPJ ${c.cnpj}, com sede em ${c.address}.`,
      'Ao criar uma conta, você declara que tem poderes para contratar em nome da oficina e aceita estes termos e a política de privacidade.',
    ],
  },
  {
    title: '2. Conta e acesso',
    body: [
      'Quem cria a conta é o dono da oficina no sistema. Ele convida os funcionários, define o papel de cada um e pode desativar acessos. A oficina responde pelo uso feito com os acessos que criou.',
      'Guarde a senha em segredo. A conta do dono precisa ativar a verificação em duas etapas dentro do prazo indicado no sistema.',
    ],
  },
  {
    title: '3. Teste grátis, planos e pagamento',
    body: [
      'A conta começa com 14 dias de teste grátis, sem cartão. Para continuar depois do teste, o dono escolhe um plano. Os planos e os preços vigentes aparecem dentro do sistema, e a diferença entre eles é o número de usuários.',
      'A cobrança é mensal e feita por um parceiro de pagamentos (Asaas). Se o pagamento atrasar mais de 7 dias, a conta fica somente para leitura até a regularização. Os dados da oficina não são apagados por atraso.',
      'Reajuste de preço é avisado com pelo menos 30 dias de antecedência.',
    ],
  },
  {
    title: '4. Cancelamento',
    body: [
      'O dono pode cancelar a assinatura a qualquer momento dentro do sistema, sem multa. O acesso continua até o fim do período já pago. Não há devolução proporcional de período já pago, salvo quando a lei exigir.',
      'Depois do cancelamento, a oficina pode pedir a exportação dos seus dados pelo canal de contato. Os dados são apagados ou tornados anônimos depois do prazo indicado na política de privacidade.',
    ],
  },
  {
    title: '5. Uso correto',
    body: [
      'A oficina se compromete a usar o Prumo dentro da lei, a cadastrar dados verdadeiros e a só cadastrar dados de clientes que tenha o direito de tratar.',
      'Mensagens de WhatsApp enviadas pelo Prumo são de serviço (link do orçamento e aviso de serviço pronto). A oficina só deve marcar que o cliente aceita receber WhatsApp quando ele tiver aceitado de fato.',
      'Não é permitido tentar acessar dados de outra oficina, burlar limites do plano, nem revender o sistema.',
    ],
  },
  {
    title: '6. Dados dos clientes da oficina',
    body: [
      'Os dados de clientes, veículos, orçamentos e ordens de serviço pertencem à oficina. Para a Lei Geral de Proteção de Dados (LGPD), a oficina é a controladora desses dados e o Prumo é o operador: trata os dados só para prestar o serviço, conforme as instruções da oficina e a política de privacidade.',
      'O sistema tem as funções de exportar e de tornar anônimo o cadastro de um cliente, para a oficina atender os pedidos dos titulares.',
    ],
  },
  {
    title: '7. Assinatura do orçamento',
    body: [
      'Quando o cliente aprova o orçamento pelo link, o Prumo guarda o nome informado, a assinatura desenhada, os itens aprovados, a data e a hora, o endereço IP e o navegador usado. Esse registro serve de prova da aprovação e não pode ser alterado.',
      'O Prumo guarda o registro, mas não é parte na relação entre a oficina e o cliente dela, e não responde pelo serviço prestado pela oficina.',
    ],
  },
  {
    title: '8. Disponibilidade e suporte',
    body: [
      'Trabalhamos para manter o sistema no ar e fazemos cópias de segurança diárias, mas não há garantia de funcionamento sem interrupção. Manutenções programadas são avisadas quando possível.',
      'O suporte é feito pelos canais indicados dentro do sistema, em dias úteis.',
    ],
  },
  {
    title: '9. Limite de responsabilidade',
    body: [
      'Na medida permitida pela lei, a responsabilidade do Prumo por danos ligados ao uso do sistema fica limitada ao valor pago pela oficina nos 12 meses anteriores ao fato. Esse limite não vale para casos de dolo nem onde a lei não permitir.',
    ],
  },
  {
    title: '10. Mudanças nestes termos',
    body: ['Podemos atualizar estes termos. Mudanças relevantes são avisadas dentro do sistema ou por e-mail antes de valerem. Continuar usando o sistema depois do aviso significa aceitar a nova versão.'],
  },
  {
    title: '11. Lei e foro',
    body: [`Estes termos seguem a lei brasileira. Fica eleito o foro de ${c.forum} para resolver qualquer questão, salvo quando a lei garantir outro foro.`],
  },
]

const privacy: Section[] = [
  {
    title: '1. Quem somos',
    body: [
      `O Prumo é oferecido por ${c.legalName}, CNPJ ${c.cnpj}, com sede em ${c.address}. Encarregado pelo tratamento de dados: ${c.dpoName}, ${c.dpoEmail}.`,
      'Esta política explica quais dados pessoais tratamos, para quê e por quanto tempo.',
    ],
  },
  {
    title: '2. Dois papéis diferentes',
    body: [
      'Dados da conta da oficina (dono e funcionários): somos o controlador.',
      'Dados dos clientes da oficina (quem leva o carro): a oficina é a controladora e nós somos o operador. Se você é cliente de uma oficina e quer consultar, corrigir ou apagar seus dados, procure a oficina. Se pedir a nós, encaminhamos o pedido a ela.',
    ],
  },
  {
    title: '3. Dados que tratamos',
    body: [
      'Da conta: nome da oficina, nome e e-mail de cada usuário, senha (guardada de forma que não pode ser lida), papel no sistema e, para assinar um plano, CPF ou CNPJ do dono.',
      'Dos clientes da oficina, cadastrados por ela: nome, telefone, e-mail, CPF ou CNPJ, placa e dados do veículo, orçamentos, ordens de serviço e fotos do serviço. Telefone e CPF/CNPJ ficam criptografados no banco de dados.',
      'Da aprovação do orçamento: nome de quem assinou, assinatura desenhada, itens aprovados, data e hora, endereço IP e navegador.',
      'Das mensagens de WhatsApp: número de destino, conteúdo do aviso e situação da entrega.',
      'Técnicos: registros de erro do sistema, sem dados pessoais, e cookies estritamente necessários para manter a sessão. Não usamos cookies de publicidade.',
    ],
  },
  {
    title: '4. Para que usamos',
    body: [
      'Para prestar o serviço contratado (execução de contrato), cobrar a assinatura, dar suporte, manter a segurança do sistema e cumprir obrigações legais.',
      'Não vendemos dados e não usamos os dados dos clientes da oficina para propaganda.',
    ],
  },
  {
    title: '5. Com quem compartilhamos',
    body: [
      'Só com fornecedores necessários para o serviço funcionar: hospedagem do sistema e do banco de dados; armazenamento de arquivos (Cloudflare R2), para fotos e assinaturas; pagamentos (Asaas), para a assinatura do plano; WhatsApp Business (Meta), para enviar os avisos; envio de e-mail, para mensagens da conta como a de senha nova; e monitoramento de erros (Sentry), que não recebe dados pessoais.',
      'Alguns desses fornecedores guardam dados fora do Brasil. Nesses casos exigimos garantias de proteção compatíveis com a LGPD.',
      'Podemos entregar dados a autoridades quando a lei ou uma ordem judicial obrigar.',
    ],
  },
  {
    title: '6. Por quanto tempo guardamos',
    body: [
      'Enquanto a conta da oficina estiver ativa. Depois do cancelamento, guardamos os dados por até 90 dias, para a oficina poder exportar ou voltar, e então apagamos ou tornamos anônimos. Podemos guardar por mais tempo o que a lei obrigar, como registros de cobrança.',
      'Quando a oficina torna anônimo o cadastro de um cliente, os dados que o identificam são apagados. Orçamentos, ordens de serviço e o registro da aprovação continuam, sem identificar a pessoa.',
    ],
  },
  {
    title: '7. Seus direitos',
    body: [
      'Pela LGPD, você pode pedir confirmação de que tratamos seus dados, acesso, correção, anonimização, eliminação, portabilidade e informação sobre com quem compartilhamos. Usuários da conta pedem pelo contato abaixo. Clientes de oficina pedem à oficina.',
      'Você também pode reclamar à Autoridade Nacional de Proteção de Dados (ANPD).',
    ],
  },
  {
    title: '8. Segurança',
    body: [
      'Usamos conexão criptografada, criptografia de dados sensíveis no banco, separação dos dados de cada oficina, verificação em duas etapas para o dono, arquivos em área privada com links de acesso temporários e cópias de segurança diárias. Nenhum sistema é imune a falhas; se houver um incidente que possa causar risco relevante, avisamos a oficina e a ANPD como a lei manda.',
    ],
  },
  {
    title: '9. Contato',
    body: [`Dúvidas e pedidos sobre dados pessoais: ${c.contactEmail}.`],
  },
]

function LegalPage({ title, sections, other }: { title: string; sections: Section[]; other: { to: string; label: string } }) {
  return (
    <Box component="main" sx={{ maxWidth: 720, mx: 'auto', px: 2.5, py: 5 }}>
      <Link to="/login" aria-label="Prumo: ir para o login">
        <Logo height={30} />
      </Link>
      <Typography variant="h1" sx={{ fontSize: 28, mt: 4 }}>
        {title}
      </Typography>
      <Typography variant="body2" color="text.secondary" sx={{ mt: 0.5 }}>
        Versão de {formatDate(c.version)}
      </Typography>

      {sections.map((section) => (
        <Box component="section" key={section.title} sx={{ mt: 4 }}>
          <Typography variant="h2" sx={{ fontSize: 18, mb: 1 }}>
            {section.title}
          </Typography>
          {section.body.map((paragraph) => (
            <Typography key={paragraph} sx={{ mb: 1.5 }}>
              {paragraph}
            </Typography>
          ))}
        </Box>
      ))}

      <Typography sx={{ mt: 5 }}>
        Veja também: <MuiLink component={Link} to={other.to}>{other.label}</MuiLink>
      </Typography>
    </Box>
  )
}

export function TermsPage() {
  return <LegalPage title="Termos de uso" sections={terms} other={{ to: '/privacidade', label: 'Política de privacidade' }} />
}

export function PrivacyPage() {
  return <LegalPage title="Política de privacidade" sections={privacy} other={{ to: '/termos', label: 'Termos de uso' }} />
}
