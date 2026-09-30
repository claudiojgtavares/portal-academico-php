<?php
require_once __DIR__ . '/../includes/portal_layout.php';

require_login();

$user = current_user();
$role = portal_user_role($user);
$db = portal_one($pdo, "SELECT * FROM users WHERE id = ? LIMIT 1", [(int) $user['id']]) ?: $user;
[$success, $error] = portal_flash();

function editar_perfil_data_pt(?string $value): string {
    if (!$value) return '';
    $t = strtotime($value);
    return $t ? date('d/m/Y', $t) : $value;
}

portal_layout_start('perfil', 'Editar perfil', 'Atualize dados pessoais, fotografia e documento de identificação.', $role);
?>

<?php if ($success): ?><div class="ui-alert ui-alert-success"><?= e($success); ?></div><?php endif; ?>
<?php if ($error): ?><div class="ui-alert ui-alert-error"><?= e($error); ?></div><?php endif; ?>

<div class="ui-panel">
    <div class="ui-panel-header">
        <div>
            <h2>Dados pessoais</h2>
            <p>Todos os perfis podem atualizar fotografia e dados pessoais. ID e email institucional continuam geridos pelo sistema.</p>
        </div>
        <a class="ui-btn ui-btn-ghost" href="<?= e(APP_URL); ?>/pages/perfil.php">Voltar ao perfil</a>
    </div>

    <div class="ui-panel-body">
        <form action="<?= e(APP_URL); ?>/actions/atualizar_perfil.php" method="POST" enctype="multipart/form-data" class="ui-form-grid">
            <div class="ui-field" style="grid-column:1/-1;">
                <label>Nome completo</label>
                <input class="ui-control" name="full_name" value="<?= e($db['full_name'] ?? ''); ?>" required>
            </div>

            <div class="ui-field">
                <label>ID institucional</label>
                <input class="ui-control" value="<?= e($db['institutional_id'] ?? ''); ?>" disabled>
                <small>Campo gerido pelo sistema.</small>
            </div>

            <div class="ui-field">
                <label>Email institucional</label>
                <input class="ui-control" value="<?= e($db['email'] ?? ''); ?>" disabled>
                <small>Campo gerido pelo sistema.</small>
            </div>

            <div class="ui-field">
                <label>Email pessoal</label>
                <input class="ui-control" type="email" name="personal_email" value="<?= e($db['personal_email'] ?? ''); ?>" placeholder="exemplo@email.com">
            </div>

            <div class="ui-field">
                <label>Telefone</label>
                <input class="ui-control" name="phone" value="<?= e($db['phone'] ?? ''); ?>" placeholder="+238 900 00 00">
            </div>

            <div class="ui-field">
                <label>Data de nascimento</label>
                <input class="ui-control" type="text" name="birth_date_visible" value="<?= e(editar_perfil_data_pt($db['birth_date'] ?? null)); ?>" placeholder="dd/mm/aaaa" maxlength="10" inputmode="numeric">
                <input type="hidden" name="birth_date" id="profile_birth_date" value="<?= e($db['birth_date'] ?? ''); ?>">
            </div>

            <div class="ui-field">
                <label>Tipo de documento</label>
                <input class="ui-control" name="document_type" value="<?= e($db['document_type'] ?? ''); ?>" placeholder="BI, Passaporte, Cartão nacional...">
            </div>

            <div class="ui-field">
                <label>N.º do documento</label>
                <input class="ui-control" name="document_number" value="<?= e($db['document_number'] ?? ''); ?>">
            </div>

            <div class="ui-field" style="grid-column:1/-1;">
                <label>Morada</label>
                <textarea class="ui-control" name="address" placeholder="Morada atual do utilizador"><?= e($db['address'] ?? ''); ?></textarea>
            </div>

            <div class="ui-field">
                <label>Fotografia de perfil</label>
                <label class="ui-file-control"><span>Escolher fotografia</span><input type="file" name="profile_photo" accept=".jpg,.jpeg,.png,.webp"></label>
                <small>Formatos aceites: JPG, PNG ou WEBP.</small>
            </div>

            <div class="ui-field">
                <label>Novo documento de identificação</label>
                <label class="ui-file-control"><span>Escolher documento</span><input type="file" name="identity_document_file" accept=".pdf,.jpg,.jpeg,.png"></label>
                <small>Use quando houver novo BI, passaporte ou documento atualizado.</small>
            </div>

            <div class="ui-actions" style="grid-column:1/-1;">
                <button class="ui-btn ui-btn-primary" type="submit">Guardar alterações</button>
                <a class="ui-btn ui-btn-ghost" href="<?= e(APP_URL); ?>/pages/perfil.php">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script>
(function(){
  function normalizarDataPerfil(valor){
    var m = String(valor || '').match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
    if(!m) return '';
    return m[3] + '-' + m[2] + '-' + m[1];
  }
  var input = document.querySelector('input[name="birth_date_visible"]');
  var hidden = document.getElementById('profile_birth_date');
  if(input && hidden){
    input.addEventListener('input', function(){
      var v = input.value.replace(/\D/g,'').slice(0,8);
      if(v.length >= 5) input.value = v.slice(0,2)+'/'+v.slice(2,4)+'/'+v.slice(4);
      else if(v.length >= 3) input.value = v.slice(0,2)+'/'+v.slice(2);
      else input.value = v;
      hidden.value = normalizarDataPerfil(input.value);
    });
    var form = input.closest('form');
    if(form){ form.addEventListener('submit', function(){ hidden.value = normalizarDataPerfil(input.value); }); }
  }
})();
</script>

<?php portal_layout_end(); ?>
