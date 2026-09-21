# Guía rápida de Git

## 📥 Bajar últimos cambios del remoto

```bash
# Si no tenés cambios locales sin commitear
git pull origin main

# Si tenés cambios locales que querés preservar
git stash
git pull origin main
git stash pop

# Si querés sobrescribir TODO con lo que está en remoto (peligro: borra cambios locales)
git fetch origin && git reset --hard origin/main
```

## 🌿 Ramas (branches)

```bash
# Crear una rama nueva y moverse a ella
git checkout -b nombre-de-la-rama

# Moverse a una rama existente
git checkout nombre-de-la-rama

# Listar ramas
git branch          # locales
git branch -a       # todas (locales + remotas)

# Eliminar una rama (local)
git branch -d nombre-de-la-rama

# Eliminar una rama del remoto
git push origin --delete nombre-de-la-rama
```

## 📤 Subir cambios

```bash
# 1. Ver qué cambió
git status
git diff           # cambios sin stagear

# 2. Agregar archivos al stage
git add .                          # todos
git add archivo.php                # uno específico
git add src/carpeta/               # una carpeta

# 3. Crear el commit
git commit -m "descripción corta del cambio"

# 4. Subir al remoto
git push origin nombre-de-la-rama  # primera vez
git push                           # siguientes veces (si ya tiene upstream)
```

## 🔀 Unir ramas (merge)

```bash
# Pararse en la rama destino (ej: main)
git checkout main

# Asegurarse de tener lo último
git pull origin main

# Fusionar la rama de trabajo
git merge nombre-de-la-rama
```

## 🧹 Utilidades

```bash
# Guardar cambios temporales
git stash                # guarda y limpia el working directory
git stash pop            # recupera lo guardado
git stash list           # lista los stashes

# Ver historial
git log --oneline        # resumen compacto
git log --oneline --graph --all  # árbol visual

# Deshacer cosas
git checkout -- archivo.php        # descartar cambios en un archivo
git reset HEAD archivo.php         # sacar archivo del stage
git reset --soft HEAD~1            # deshacer último commit (sin perder cambios)
git reset --hard HEAD~1            # deshacer último commit (borra cambios)

# Actualizar rama con cambios de main (sin merge directo)
git checkout mi-rama
git rebase main
```

## 🔄 Flujo típico diario

```bash
# 1. Bajar lo último de main
git checkout main
git pull origin main

# 2. Crear rama para tu tarea
git checkout -b feature/lo-que-sea

# 3. Trabajar... hacer cambios...
git add .
git commit -m "agrega x cosa"

# 4. Subir
git push origin feature/lo-que-sea

# 5. Cuando terminás, volver a main y mergear
git checkout main
git pull origin main
git merge feature/lo-que-sea
git push origin main
```
