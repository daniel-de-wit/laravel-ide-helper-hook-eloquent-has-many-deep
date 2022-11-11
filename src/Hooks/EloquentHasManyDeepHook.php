<?php

declare(strict_types=1);

namespace DanielDeWit\LaravelIdeHelperHookEloquentHasManyDeep\Hooks;

use Barryvdh\LaravelIdeHelper\Console\ModelsCommand;
use Barryvdh\LaravelIdeHelper\Contracts\ModelHookInterface;
use Barryvdh\Reflection\DocBlock;
use Barryvdh\Reflection\DocBlock\Context;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use phpDocumentor\Reflection\Types\ContextFactory;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionObject;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Staudenmeir\EloquentHasManyDeep\HasOneDeep;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;
use Throwable;

class EloquentHasManyDeepHook implements ModelHookInterface
{
    protected const RELATION_TYPES = [
        'hasManyDeep' => HasManyDeep::class,
        'hasOneDeep'  => HasOneDeep::class,
    ];

    /**
     * @throws \ReflectionException
     */
    public function run(ModelsCommand $command, Model $model): void
    {
        if (! in_array(HasRelationships::class, class_uses_recursive($model))) {
            return;
        };

        foreach (get_class_methods($model) as $method) {
            $reflection = new ReflectionMethod($model, $method);

            if ($returnType = $reflection->getReturnType()) {
                $type = $returnType instanceof ReflectionNamedType
                    ? $returnType->getName()
                    : (string) $returnType;
            } else {
                // php 7.x type or fallback to docblock
                $type = (string) $this->getReturnTypeFromDocBlock($reflection);
            }

            $fileName = $reflection->getFileName();

            if (! $fileName) {
                return;
            }

            $file = new \SplFileObject($fileName);
            $file->seek($reflection->getStartLine() - 1);

            $code = '';
            while ($file->key() < $reflection->getEndLine()) {
                $code .= $file->current();
                $file->next();
            }

            foreach (
                self::RELATION_TYPES as $relation => $impl
            ) {
                $search = '$this->' . $relation . '(';
                if (stripos($code, $search) || ltrim($impl, '\\') === ltrim((string) $type, '\\')) {
                    //Resolve the relation's model to a Relation object.
                    $methodReflection = new ReflectionMethod($model, $method);
                    if ($methodReflection->getNumberOfParameters()) {
                        continue;
                    }

                    $comment = $this->getCommentFromDocBlock($reflection);
                    // Adding constraints requires reading model properties which
                    // can cause errors. Since we don't need constraints we can
                    // disable them when we fetch the relation to avoid errors.
                    $relationObj = Relation::noConstraints(function () use ($model, $method) {
                        try {
                            return $model->$method();
                        } catch (Throwable $e) {
                            return null;
                        }
                    });

                    if ($relationObj instanceof Relation) {
                        $relatedModel = $this->getClassNameInDestinationFile(
                            $model,
                            get_class($relationObj->getRelated())
                        );

                        if ($relationObj instanceof HasOneDeep) {
                            $command->setProperty(
                                $method,
                                $relatedModel,
                                true,
                                null,
                                $comment,
                                true,
                            );

                            return;
                        }

                        if ($relationObj instanceof HasManyDeep) {
                            $relatedClass = '\\' . get_class($relationObj->getRelated());
                            $collectionClass = $this->getCollectionClass($relatedClass);
                            $collectionClassNameInModel = $this->getClassNameInDestinationFile(
                                $model,
                                $collectionClass
                            );

                            $command->setProperty(
                                $method,
                                $collectionClassNameInModel . '|' . $relatedModel . '[]',
                                true,
                                null,
                                $comment
                            );
                        }
                    }
                }
            }
        }
    }

    protected function getReturnTypeFromDocBlock(ReflectionMethod $reflection): ?string
    {
        $phpDocContext = (new ContextFactory())->createFromReflector($reflection);
        $context = new Context(
            $phpDocContext->getNamespace(),
            $phpDocContext->getNamespaceAliases()
        );
        $type = null;
        $phpdoc = new DocBlock($reflection, $context);

        if ($phpdoc->hasTag('return')) {
            // @phpstan-ignore-next-line
            $type = $phpdoc->getTagsByName('return')[0]->getType();
        }

        return $type;
    }

    protected function getCommentFromDocBlock(ReflectionMethod $reflection): ?string
    {
        $phpDocContext = (new ContextFactory())->createFromReflector($reflection);
        $context = new Context(
            $phpDocContext->getNamespace(),
            $phpDocContext->getNamespaceAliases()
        );
        $comment = '';
        $phpdoc = new DocBlock($reflection, $context);

        if ($phpdoc->hasTag('comment')) {
            $comment = $phpdoc->getTagsByName('comment')[0]->getContent();
        }

        return $comment;
    }

    protected function getClassNameInDestinationFile(object $model, string $className): string
    {
        $reflection = $model instanceof ReflectionClass
            ? $model
            : new ReflectionObject($model);

        $className = trim($className, '\\');
        $usedClassNames = $this->getUsedClassNames($reflection);
        return $usedClassNames[$className] ?? ('\\' . $className);
    }

    /**
     * @param ReflectionClass $reflection
     * @return array<string, string>
     */
    protected function getUsedClassNames(ReflectionClass $reflection): array
    {
        $namespaceAliases = array_flip((new ContextFactory())->createFromReflector($reflection)->getNamespaceAliases());
        $namespaceAliases[$reflection->getName()] = $reflection->getShortName();

        return $namespaceAliases;
    }

    protected function getCollectionClass(string $className): string
    {
        // Return something in the very very unlikely scenario the model doesn't
        // have a newCollection() method.
        if (! method_exists($className, 'newCollection')) {
            return '\Illuminate\Database\Eloquent\Collection';
        }

        /** @var \Illuminate\Database\Eloquent\Model $model */
        $model = new $className();
        return '\\' . get_class($model->newCollection());
    }
}
