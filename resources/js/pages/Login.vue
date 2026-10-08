<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
const props = withDefaults(defineProps<{mode?:string;token?:string;email?:string;googleUrl?:string|null;googleLink?:string|null;googleLinkUrl?:string;googleCancelUrl?:string}>(), {mode:'login'});
const form = useForm({email:props.googleLink??props.email??'',password:'',password_confirmation:'',token:props.token??''});
function submit() {
  const url = props.googleLink ? props.googleLinkUrl! : props.mode==='login'?'/login':props.mode==='forgot'?'/forgot-password':'/reset-password';
  form.post(url,{onFinish:()=>form.reset('password','password_confirmation')});
}
</script>
<template>
  <div class="login-page">
    <Head title="Acceso"/>
    <section class="login-story">
      <div class="brand"><span class="brand-mark">E<span>2</span></span>ERRONK2D</div>
      <div><h1>Cada reto,<br>una oportunidad<br>para crecer<span>.</span></h1><p>Todo el proceso de evaluación en un mismo lugar.<br>Más tiempo para acompañar a tus estudiantes.</p></div>
      <span class="story-footer">EQUIPO → PERSONA → APRENDIZAJE</span>
    </section>
    <section class="login-form">
      <p class="eyebrow">TU ESPACIO DE TRABAJO</p>
      <h2>{{googleLink?'Vincula tu cuenta':mode==='login'?'Bienvenido de nuevo':mode==='forgot'?'Recupera tu acceso':'Nueva contraseña'}}</h2>
      <p class="muted">{{googleLink?'Ya tienes una cuenta en Erronk2D. Confirma su contraseña una sola vez para poder entrar con Google.':mode==='login'?'Accede con tu cuenta del centro.':'Utiliza el correo de tu cuenta.'}}</p>
      <template v-if="mode==='login'&&googleUrl&&!googleLink">
        <a :href="googleUrl" class="button full">Continuar con Google</a>
        <p class="helper">Puedes iniciar sesión o solicitar el registro. Las cuentas nuevas necesitan aprobación de la administración.</p>
      </template>
      <form @submit.prevent="submit">
        <label>Correo electrónico<input v-model="form.email" type="email" required :readonly="Boolean(googleLink)" autocomplete="username" placeholder="nombre@centro.eus"/></label>
        <label v-if="mode!=='forgot'">Contraseña<input v-model="form.password" type="password" required :minlength="mode==='reset'?10:undefined" :autocomplete="mode==='login'?'current-password':'new-password'"/></label>
        <label v-if="mode==='reset'">Repite la contraseña<input v-model="form.password_confirmation" type="password" required minlength="10" autocomplete="new-password"/></label>
        <p v-for="(error,key) in $page.props.errors" :key="key" class="notice error" role="alert">{{error}}</p>
        <p v-if="($page.props.flash as any)?.success" class="notice success">{{($page.props.flash as any).success}}</p>
        <button class="button primary full" :disabled="form.processing">{{form.processing?'Un momento…':googleLink?'Vincular y entrar':mode==='login'?'Entrar a Erronk2D →':mode==='forgot'?'Enviar enlace':'Guardar contraseña'}}</button>
        <Link :href="mode==='login'?'/forgot-password':'/login'" class="text-link">{{mode==='login'?'¿Has olvidado tu contraseña?':'Volver al acceso'}}</Link>
        <a v-if="googleLink" :href="googleCancelUrl" class="text-link">Cancelar vinculación</a>
      </form>
    </section>
  </div>
</template>
