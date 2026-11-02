<?php

namespace App\Http\Requests\Concerns;

trait ValidatesActorName
{
    /**
     * Sin autenticación el nombre es autodeclarado, pero nadie debe poder firmar como
     * el sistema: falsificaría las correcciones del diagnóstico en el historial.
     *
     * @return list<string>
     */
    protected function actorNameRules(): array
    {
        return ['required', 'string', 'max:100', 'not_regex:/^system:/i'];
    }
}
