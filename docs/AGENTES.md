# Agentes de mantenimiento de StruchVet

## Organización

`AGENTS.md` establece las reglas comunes. Los archivos `.codex/agents/*.toml` registran cinco roles de proyecto para clientes Codex compatibles. Cada uno contiene `name`, `description` y `developer_instructions`; omite modelo y esfuerzo para conservar la elección de la sesión. Las instrucciones detalladas están en `.agents/agents/`.

| Rol registrado | Guía | Responsabilidad |
|---|---|---|
| `gestion_php` | `.agents/agents/gestion_php.md` | Reglas, inventario, MariaDB, permisos y auditoría |
| `ecommerce` | `.agents/agents/ecommerce.md` | Astro, catálogo, carrito y experiencia web |
| `electron_desktop` | `.agents/agents/electron_desktop.md` | Runtime PHP, procesos, IPC e instaladores |
| `actualizaciones` | `.agents/agents/actualizaciones.md` | Versiones, releases, integridad y recuperación |
| `calidad` | `.agents/agents/calidad.md` | Regresiones, pruebas y evidencia |

Las doce guías de `.agents/subagents/` cubren frontend, PHP, base de datos, pruebas funcionales, seguridad, accesibilidad, compilaciones, responsive, Linux, Windows, documentación y versionado/migraciones. Son especialidades para tareas acotadas, no doce servicios permanentes ni agentes duplicados. Un rol registrado puede aplicar la guía o entregarla a un subagente disponible.

## Uso

Pedir explícitamente el rol y resultado deseado. Ejemplos:

- «Usa gestion_php para revisar que una salida de inventario no se duplique. Encarga a calidad una revisión independiente de las pruebas.»
- «Usa electron_desktop para investigar el cierre del servicio PHP en Linux. Aplica la especialidad linux y limita los cambios al ciclo de procesos.»
- «Usa actualizaciones para preparar una versión de prueba, sin publicar una estable. Sigue la skill desktop-release y entrega la matriz de verificación.»

Antes de trabajo paralelo, el coordinador asigna archivos exclusivos y acuerda contratos entre áreas. Si dos tareas necesitan el mismo archivo, se ejecutan secuencialmente o una devuelve hallazgos sin editar. Cada entrega debe identificar archivos, evidencia y pendientes; no basta afirmar que un agente lo revisó.

`.agents/skills/desktop-release/SKILL.md` contiene el procedimiento de preparación y validación de una entrega. No ejecuta comandos automáticamente. Puede pedirse por su nombre si el cliente la descubre; si no aparece en su catálogo, se puede pedir que lea y siga el archivo.

## Compatibilidad y límites

La documentación oficial de [instrucciones AGENTS.md](https://learn.chatgpt.com/docs/agent-configuration/agents-md) explica cómo Codex incorpora instrucciones del repositorio. La de [subagentes](https://learn.chatgpt.com/docs/agent-configuration/subagents) describe los archivos de roles en `.codex/agents/` y la herencia de permisos de la sesión principal.

Los roles de este repositorio no alteran permisos, proveedor, servidores MCP ni modelo. No se activa una función del cliente instalando estas carpetas. La disponibilidad depende del cliente, la versión y las herramientas habilitadas. Si el cliente no permite ejecución paralela o roles personalizados, el agente principal lee las mismas guías y trabaja secuencialmente; debe declarar esa limitación.

No hay bots, agentes autónomos, tareas programadas ni conexiones a una API de IA añadidas a la aplicación veterinaria. Ninguno de estos archivos envía datos de clientes a un proveedor ni contiene una clave API. Son instrucciones de desarrollo del repositorio.

## Validación de configuración

Los TOML se pueden comprobar sin iniciar agentes:

```bash
python3 - <<'PYTHON'
from pathlib import Path
import tomllib
for path in sorted(Path('.codex/agents').glob('*.toml')):
    role = tomllib.loads(path.read_text())
    assert all(isinstance(role.get(k), str) and role[k].strip()
               for k in ('name', 'description', 'developer_instructions'))
    print(path, role['name'])
PYTHON
```

Esta validación comprueba la sintaxis y los campos, no que un cliente concreto haya cargado o ejecutado los roles. Tras incorporar la configuración, abrir una sesión nueva o seguir el procedimiento de recarga de ese cliente antes de probar los nombres registrados.
