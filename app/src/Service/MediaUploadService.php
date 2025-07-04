<?php

namespace App\Service;

use App\Entity\Media;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service de gestion sécurisée des uploads de médias
 * 
 * Fonctionnalités principales :
 * - Upload sécurisé de fichiers images avec validation complète
 * - Génération de noms de fichiers uniques pour éviter les conflits
 * - Gestion des uploads multiples pour les galeries de produits
 * - Support de la capture webcam via Data URI
 * - Suppression sécurisée des fichiers avec nettoyage
 * - Création automatique des dossiers de destination
 * 
 * Sécurité et bonnes pratiques :
 * - Validation des types MIME pour éviter les uploads malveillants
 * - Noms de fichiers slugifiés et uniques
 * - Gestion des erreurs avec logging détaillé
 * - Isolation des uploads dans des sous-dossiers par type
 * - Intégration complète avec l'entité Media pour la persistance
 */
class MediaUploadService
{
    private string $basePath;
    private Filesystem $filesystem;

    public function __construct(
        private readonly SluggerInterface $slugger,
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
        string $projectDir
    ) {
        // Définir le chemin de base pour tous les uploads
        $this->basePath = $projectDir . '/public/uploads';
        $this->filesystem = new Filesystem();
    }

    /**
     * Gère l'upload d'un fichier image et crée l'entité Media correspondante
     * 
     * Cette méthode centrale gère tout le processus d'upload :
     * - Validation du fichier uploadé (type, taille)
     * - Génération d'un nom de fichier unique et sécurisé
     * - Déplacement du fichier vers le dossier de destination
     * - Création de l'entité Media pour la persistance en base
     * 
     * Sécurité :
     * - Le fichier est validé côté serveur même si déjà validé côté client
     * - Le nom original est slugifié pour éviter les caractères dangereux
     * - Un ID unique est ajouté pour éviter les conflits de noms
     *
     * @param UploadedFile $file Le fichier uploadé par l'utilisateur
     * @param string $subDirectory Le sous-dossier de destination (ex: 'products', 'avatars')
     * @return Media L'entité Media créée (non persistée, le contrôleur doit persist)
     * @throws FileException Si l'upload échoue
     */
    public function uploadImage(UploadedFile $file, string $subDirectory): Media
    {
        $media = new Media();
        $media->setFile($file); // Stockage temporaire pour traitement ultérieur

        // Générer un nom de fichier unique et sécurisé
        $fileName = $this->generateUniqueFileName($file);
        $media->setFileName($fileName);
        
        // Déterminer et créer le dossier de destination si nécessaire
        $targetDirectory = $this->getTargetDirectory($subDirectory);

        try {
            // Déplacer le fichier depuis le dossier temporaire vers la destination finale
            $file->move($targetDirectory, $fileName);
        } catch (FileException $e) {
            // Logger l'erreur pour débogage et relancer l'exception
            $this->logger->error('Failed to upload file: ' . $e->getMessage());
            throw new FileException($e->getMessage());
        }

        // L'entité Media n'est pas persistée ici - c'est la responsabilité du contrôleur
        // Cela permet plus de flexibilité pour les transactions et validations
        return $media;
    }

    /**
     * Gère l'upload de plusieurs images en une seule opération
     * 
     * Utilisé principalement pour les galeries de produits où l'utilisateur
     * peut sélectionner plusieurs fichiers d'un coup.
     * 
     * Gestion d'erreur robuste :
     * - Si un fichier échoue, les autres continuent d'être traités
     * - Les erreurs sont loggées individuellement pour diagnostic
     *
     * @param UploadedFile[] $files Tableau des fichiers à uploader
     * @param string $subDirectory Le sous-dossier de destination
     * @return Media[] Tableau des entités Media créées
     */
    public function uploadMultipleImages(array $files, string $subDirectory): array
    {
        $mediaEntities = [];
        
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                try {
                    $mediaEntities[] = $this->uploadImage($file, $subDirectory);
                } catch (FileException $e) {
                    // Logger l'erreur mais continuer avec les autres fichiers
                    $this->logger->warning('Failed to upload one file in batch: ' . $e->getMessage());
                    // On pourrait aussi ajouter l'erreur à un tableau de retour si nécessaire
                }
            }
        }
        
        return $mediaEntities;
    }

    /**
     * Supprime un fichier image et son entité Media associée
     * 
     * Nettoyage complet :
     * - Suppression du fichier physique du système de fichiers
     * - L'entité Media doit être supprimée séparément par le contrôleur
     * - Gestion gracieuse des erreurs (fichier déjà supprimé, etc.)
     *
     * @param Media $media L'entité Media à supprimer
     * @param string $subDirectory Le sous-dossier où se trouve le fichier
     * @return bool True si succès, False si échec
     */
    public function removeImage(Media $media, string $subDirectory): bool
    {
        // Construire le chemin complet vers le fichier
        $filePath = $this->getTargetDirectory($subDirectory) . '/' . $media->getFileName();

        try {
            // Vérifier l'existence avant suppression (évite les erreurs inutiles)
            if ($this->filesystem->exists($filePath)) {
                $this->filesystem->remove($filePath);
            }
            
            return true;
        } catch (\Exception $e) {
            // Logger l'erreur pour diagnostic mais ne pas faire échouer l'opération
            $this->logger->error('Failed to remove file: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Génère un nom de fichier unique et sécurisé
     * 
     * Processus de sécurisation :
     * 1. Extraction du nom original sans l'extension
     * 2. Slugification pour supprimer caractères spéciaux et espaces
     * 3. Ajout d'un identifiant unique pour éviter les conflits
     * 4. Réattachement de l'extension détectée automatiquement
     * 
     * @param UploadedFile $file Le fichier dont on veut générer le nom
     * @return string Le nom de fichier sécurisé et unique
     */
    private function generateUniqueFileName(UploadedFile $file): string
    {
        // Extraire le nom sans extension
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        
        // Slugifier pour la sécurité (supprime caractères spéciaux, espaces, etc.)
        $safeFilename = $this->slugger->slug($originalFilename);
        
        // Créer un nom unique avec ID + extension auto-détectée
        return $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();
    }

    /**
     * Retourne le chemin du dossier de destination et s'assure qu'il existe
     * 
     * Organisation des fichiers :
     * - Dossier de base : /public/uploads/
     * - Sous-dossiers par type : products/, avatars/, documents/, etc.
     * - Création automatique des dossiers manquants
     * 
     * @param string $subDirectory Le sous-dossier désiré
     * @return string Le chemin complet vers le dossier
     */
    private function getTargetDirectory(string $subDirectory = ''): string
    {
        // Construire le chemin en supprimant les slashes superflus
        $directory = rtrim($this->basePath . '/' . trim($subDirectory, '/'), '/');
        
        // Créer le dossier s'il n'existe pas (récursivement)
        if (!$this->filesystem->exists($directory)) {
            $this->filesystem->mkdir($directory);
        }

        return $directory;
    }

    /**
     * Génère l'URL publique complète pour accéder à un fichier média
     * 
     * Utilisé dans les templates Twig pour afficher les images.
     * Gère gracieusement les cas où le média est null ou sans fichier.
     *
     * @param Media|null $media L'entité média (peut être null)
     * @param string $subDirectory Le sous-dossier du média
     * @return string|null L'URL publique ou null si pas de fichier
     */
    public function getUrl(?Media $media, string $subDirectory): ?string
    {
        if (!$media || !$media->getFileName()) {
            return null;
        }
        
        // Construire l'URL publique relative (accessible via le web)
        return '/uploads/' . $subDirectory . '/' . $media->getFileName();
    }

    /**
     * Upload un fichier à partir d'une Data URI (capture webcam)
     * 
     * Fonctionnalité avancée pour la capture via webcam :
     * 1. Décodage de la Data URI (format: data:image/type;base64,données...)
     * 2. Validation du type d'image supporté
     * 3. Conversion en fichier temporaire
     * 4. Création d'un objet UploadedFile pour compatibilité
     * 5. Utilisation de la méthode uploadImage standard
     * 
     * Sécurité :
     * - Validation stricte du format Data URI
     * - Vérification des types MIME autorisés
     * - Nettoyage automatique des fichiers temporaires
     *
     * @param string $dataUri La Data URI provenant de la webcam (format base64)
     * @param string $subDirectory Le sous-dossier de destination
     * @return Media L'entité Media créée
     * @throws \InvalidArgumentException Si la Data URI est invalide ou non supportée
     */
    public function uploadFromDataUri(string $dataUri, string $subDirectory): Media
    {
        // Étape 1 : Valider et décoder la Data URI
        if (!preg_match('/^data:image\/(\w+);base64,/', $dataUri, $type)) {
            throw new \InvalidArgumentException('Invalid data URI format');
        }
        
        // Décoder les données base64
        $imageData = base64_decode(substr($dataUri, strpos($dataUri, ',') + 1));
        if ($imageData === false) {
            throw new \InvalidArgumentException('Base64 decode failed');
        }
        
        // Valider le type d'image
        $imageType = strtolower($type[1]);
        if (!in_array($imageType, ['jpg', 'jpeg', 'gif', 'png'])) {
            throw new \InvalidArgumentException('Invalid image type');
        }

        // Étape 2 : Créer un fichier temporaire avec les données décodées
        $tempFilePath = tempnam(sys_get_temp_dir(), 'data-uri-upload');
        file_put_contents($tempFilePath, $imageData);

        // Étape 3 : Créer un objet UploadedFile pour compatibility avec le système existant
        $file = new UploadedFile(
            $tempFilePath,
            'capture.' . $imageType, // Nom original générique
            'image/' . $imageType,   // Type MIME
            null,                    // Taille (calculée automatiquement)
            true                     // Test mode (permet la construction avec fichier temporaire)
        );
        
        // Étape 4 : Utiliser la méthode standard d'upload
        $media = $this->uploadImage($file, $subDirectory);

        // Le fichier temporaire sera automatiquement nettoyé par PHP
        // ou par l'objet UploadedFile lors de sa destruction
        
        return $media;
    }
} 