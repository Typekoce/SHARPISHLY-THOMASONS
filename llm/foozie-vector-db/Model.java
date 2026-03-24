import java.io.*;
import java.nio.file.*;
import java.util.*;
import java.util.stream.Collectors;
// Location: llm/foozie-vector-dm

public class Model {

    public static class VectorItem {
        public final String id;
        public final double[] vector;
        public final String metadata;

        VectorItem(String id, double[] vector, String metadata) {
            this.id = id;
            this.vector = vector;
            this.metadata = metadata;
        }
    }

    private final List<VectorItem> items = new ArrayList<>();
    private final Path storagePath;

    public Model(String storageFile) {
        this.storagePath = Paths.get(storageFile);
        load();
    }

    /* =========================
       CRUD
       ========================= */

    public synchronized void add(String id, double[] vector, String metadata) {
        delete(id); // prevent duplicates
        items.add(new VectorItem(id, normalize(vector), metadata == null ? "" : metadata));
        save();
    }

    public synchronized boolean delete(String id) {
        boolean removed = items.removeIf(x -> x.id.equals(id));
        if (removed) save();
        return removed;
    }

    public List<VectorItem> all() {
        return Collections.unmodifiableList(items);
    }

    /* =========================
       SEARCH
       ========================= */

    public VectorItem findBest(double[] query) {
        List<VectorItem> top = findTopK(query, 1);
        return top.isEmpty() ? null : top.get(0);
    }

    public List<VectorItem> findTopK(double[] query, int k) {
        double[] normQuery = normalize(query);

        return items.stream()
                .sorted((a, b) -> Double.compare(
                        cosineSimilarity(normQuery, b.vector),
                        cosineSimilarity(normQuery, a.vector)))
                .limit(k)
                .collect(Collectors.toList());
    }

    public double similarity(double[] a, double[] b) {
        return cosineSimilarity(normalize(a), normalize(b));
    }

    /* =========================
       VECTOR MATH
       ========================= */

    private double cosineSimilarity(double[] a, double[] b) {
        if (a.length != b.length) {
            throw new IllegalArgumentException("Vector size mismatch");
        }

        double dot = 0;
        double magA = 0;
        double magB = 0;

        for (int i = 0; i < a.length; i++) {
            dot += a[i] * b[i];
            magA += a[i] * a[i];
            magB += b[i] * b[i];
        }

        if (magA == 0 || magB == 0) return 0.0;

        return dot / (Math.sqrt(magA) * Math.sqrt(magB));
    }

    private double[] normalize(double[] vec) {
        double mag = 0;

        for (double v : vec) {
            mag += v * v;
        }

        mag = Math.sqrt(mag);

        if (mag == 0) return vec;

        double[] norm = new double[vec.length];

        for (int i = 0; i < vec.length; i++) {
            norm[i] = vec[i] / mag;
        }

        return norm;
    }

    /* =========================
       STORAGE
       ========================= */

    private void load() {
        try {
            Files.createDirectories(storagePath.getParent());

            if (!Files.exists(storagePath)) return;

            for (String line : Files.readAllLines(storagePath)) {
                if (line.trim().isEmpty()) continue;

                String[] parts = line.split("\\|", 3);

                String id = parts[0];

                String[] nums = parts[1].split(",");
                double[] vec = new double[nums.length];

                for (int i = 0; i < nums.length; i++) {
                    vec[i] = Double.parseDouble(nums[i]);
                }

                String meta = parts.length > 2 ? parts[2] : "";

                items.add(new VectorItem(id, vec, meta));
            }

        } catch (Exception e) {
            throw new RuntimeException("Load failed: " + e.getMessage(), e);
        }
    }

    private void save() {
        try {
            Files.createDirectories(storagePath.getParent());

            List<String> lines = new ArrayList<>();

            for (VectorItem item : items) {
                StringBuilder sb = new StringBuilder();

                sb.append(item.id).append("|");

                for (int i = 0; i < item.vector.length; i++) {
                    if (i > 0) sb.append(",");
                    sb.append(item.vector[i]);
                }

                sb.append("|").append(item.metadata.replace("\n", " "));

                lines.add(sb.toString());
            }

            Files.write(storagePath, lines);

        } catch (Exception e) {
            throw new RuntimeException("Save failed: " + e.getMessage(), e);
        }
    }
}
