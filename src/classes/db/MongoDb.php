<?php


class MongoDb {
    
    private $connection;
    private $database;
    private $collection;
    private string $host;
    private int $port;
    private string $dbName;
    private string $username;
    private string $password;

    /**
     * Constructeur de la classe MongoDB
     */
    public function __construct(
        string $host = 'localhost',
        int $port = 27017,
        string $dbName = '',
        string $username = '',
        string $password = ''
    ) {
        $this->host     = $host;
        $this->port     = $port;
        $this->dbName   = $dbName;
        $this->username = $username;
        $this->password = $password;
    }

    // -------------------------------------------------------------------------
    // CONNECT
    // -------------------------------------------------------------------------

    /**
     * Connexion à MongoDB
     *
     * @return bool
     * @throws Exception
     */
    public function connect(): bool {
        try {
            $uri = $this->buildUri();

            $this->connection = new \MongoDB\Client($uri);
            $this->database   = $this->connection->selectDatabase($this->dbName);

            return true;

        } catch (\Exception $e) {
            throw new \Exception("Erreur de connexion MongoDB : " . $e->getMessage());
        }
    }

    /**
     * Sélectionner une collection
     *
     * @param string $collectionName
     * @return $this
     * @throws Exception
     */
    public function selectCollection(string $collectionName): static {
        if (!$this->database instanceof \MongoDB\Database) {
            throw new \Exception("Aucune base de données sélectionnée.");
        }

        $this->collection = $this->database->selectCollection($collectionName);

        return $this;
    }

    /**
     * Fermer la connexion
     *
     * @return void
     */
    public function disconnect(): void {
        $this->connection = null;
        $this->database   = null;
        $this->collection = null;
    }

    // -------------------------------------------------------------------------
    // SELECT
    // -------------------------------------------------------------------------

    /**
     * Récupérer plusieurs documents
     *
     * @param array $filter
     * @param array $options (sort, limit, skip, projection...)
     * @return array
     * @throws Exception
     */
    public function select(array $filter = [], array $options = []): array {
        $this->checkCollection();

        try {
            $cursor  = $this->collection->find($filter, $options);
            $results = [];

            foreach ($cursor as $document) {
                $results[] = $this->documentToArray($document);
            }

            return $results;

        } catch (\Exception $e) {
            throw new \Exception("Erreur SELECT : " . $e->getMessage());
        }
    }

    /**
     * Récupérer un seul document
     *
     * @param array $filter
     * @param array $options
     * @return array|null
     * @throws Exception
     */
    public function selectOne(array $filter = [], array $options = []): ?array  {
        $this->checkCollection();

        try {
            $document = $this->collection->findOne($filter, $options);

            return $document ? $this->documentToArray($document) : null;

        } catch (\Exception $e) {
            throw new \Exception("Erreur SELECT ONE : " . $e->getMessage());
        }
    }

    /**
     * Compter les documents
     *
     * @param array $filter
     * @return int
     * @throws Exception
     */
    public function count(array $filter = []): int {
        $this->checkCollection();

        try {
            return $this->collection->countDocuments($filter);

        } catch (\Exception $e) {
            throw new \Exception("Erreur COUNT : " . $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // INSERT
    // -------------------------------------------------------------------------

    /**
     * Insérer un seul document
     *
     * @param array $data
     * @return string|null - L'ID du document inséré
     * @throws Exception
     */
    public function insert(array $data): ?string {
        $this->checkCollection();

        try {
            $data['created_at'] = new \MongoDB\BSON\UTCDateTime();
            $data['updated_at'] = new \MongoDB\BSON\UTCDateTime();

            $result = $this->collection->insertOne($data);

            return (string) $result->getInsertedId();

        } catch (\Exception $e) {
            throw new \Exception("Erreur INSERT : " . $e->getMessage());
        }
    }

    /**
     * Insérer plusieurs documents
     *
     * @param array $data - Tableau de documents
     * @return array - Les IDs des documents insérés
     * @throws Exception
     */
    public function insertMany(array $data): array {
        $this->checkCollection();

        try {
            $now = new \MongoDB\BSON\UTCDateTime();

            foreach ($data as &$document) {
                $document['created_at'] = $now;
                $document['updated_at'] = $now;
            }

            $result = $this->collection->insertMany($data);
            $ids    = [];

            foreach ($result->getInsertedIds() as $id) {
                $ids[] = (string) $id;
            }

            return $ids;

        } catch (\Exception $e) {
            throw new \Exception("Erreur INSERT MANY : " . $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // UPDATE
    // -------------------------------------------------------------------------

    /**
     * Mettre à jour un seul document
     *
     * @param array $filter
     * @param array $data
     * @param array $options
     * @return int - Nombre de documents modifiés
     * @throws Exception
     */
    public function update(array $filter, array $data, array $options = []): int {
        $this->checkCollection();

        try {
            $data['updated_at'] = new \MongoDB\BSON\UTCDateTime();

            $update = ['$set' => $data];
            $result = $this->collection->updateOne($filter, $update, $options);

            return $result->getModifiedCount();

        } catch (\Exception $e) {
            throw new \Exception("Erreur UPDATE : " . $e->getMessage());
        }
    }

    /**
     * Mettre à jour plusieurs documents
     *
     * @param array $filter
     * @param array $data
     * @param array $options
     * @return int - Nombre de documents modifiés
     * @throws Exception
     */
    public function updateMany(array $filter, array $data, array $options = []): int {
        $this->checkCollection();

        try {
            $data['updated_at'] = new \MongoDB\BSON\UTCDateTime();

            $update = ['$set' => $data];
            $result = $this->collection->updateMany($filter, $update, $options);

            return $result->getModifiedCount();

        } catch (\Exception $e) {
            throw new \Exception("Erreur UPDATE MANY : " . $e->getMessage());
        }
    }

    /**
     * Mettre à jour ou insérer un document (Upsert)
     *
     * @param array $filter
     * @param array $data
     * @return string|int
     * @throws Exception
     */
    public function upsert(array $filter, array $data): string|int {
        $this->checkCollection();

        try {
            $data['updated_at'] = new \MongoDB\BSON\UTCDateTime();

            $update  = ['$set' => $data];
            $options = ['upsert' => true];
            $result  = $this->collection->updateOne($filter, $update, $options);

            if ($result->getUpsertedId()) {
                return (string) $result->getUpsertedId();
            }

            return $result->getModifiedCount();

        } catch (\Exception $e) {
            throw new \Exception("Erreur UPSERT : " . $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // DELETE
    // -------------------------------------------------------------------------

    /**
     * Supprimer un seul document
     *
     * @param array $filter
     * @return int - Nombre de documents supprimés
     * @throws Exception
     */
    public function delete(array $filter): int {
        $this->checkCollection();

        try {
            $result = $this->collection->deleteOne($filter);

            return $result->getDeletedCount();

        } catch (\Exception $e) {
            throw new \Exception("Erreur DELETE : " . $e->getMessage());
        }
    }

    /**
     * Supprimer plusieurs documents
     *
     * @param array $filter
     * @return int - Nombre de documents supprimés
     * @throws Exception
     */
    public function deleteMany(array $filter): int {
        $this->checkCollection();

        try {
            $result = $this->collection->deleteMany($filter);

            return $result->getDeletedCount();

        } catch (\Exception $e) {
            throw new \Exception("Erreur DELETE MANY : " . $e->getMessage());
        }
    }

    /**
     * Supprimer tous les documents d'une collection
     *
     * @return int
     * @throws Exception
     */
    public function deleteAll(): int {
        return $this->deleteMany([]);
    }

    // -------------------------------------------------------------------------
    // MÉTHODES UTILITAIRES
    // -------------------------------------------------------------------------

    /**
     * Construire l'URI de connexion
     *
     * @return string
     */
    private function buildUri(): string {
        if (!empty($this->username) && !empty($this->password)) {
            return sprintf(
                'mongodb://%s:%s@%s:%d/%s',
                $this->username,
                $this->password,
                $this->host,
                $this->port,
                $this->dbName
            );
        }

        return sprintf('mongodb://%s:%d', $this->host, $this->port);
    }

    /**
     * Vérifier si une collection est sélectionnée
     *
     * @return void
     * @throws Exception
     */
    private function checkCollection(): void {
        if (!$this->collection instanceof \MongoDB\Collection) {
        throw new \Exception("Aucune collection sélectionnée. Utilisez selectCollection().");
    }
    }

    /**
     * Convertir un document BSON en tableau PHP
     *
     * @param mixed $document
     * @return array
     */
    private function documentToArray(mixed $document): array {
        return json_decode(json_encode($document), true);
    }

    /**
     * Convertir un ID string en ObjectId MongoDB
     *
     * @param string $id
     * @return \MongoDB\BSON\ObjectId
     * @throws Exception
     */
    public function toObjectId(string $id): \MongoDB\BSON\ObjectId {
        try {
            return new \MongoDB\BSON\ObjectId($id);
        } catch (\Exception $e) {
            throw new \Exception("ID invalide : " . $e->getMessage());
        }
    }
}
