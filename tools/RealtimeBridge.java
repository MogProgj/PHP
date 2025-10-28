import java.io.IOException;
import java.nio.charset.StandardCharsets;
import java.nio.file.Files;
import java.nio.file.Path;
import java.util.ArrayList;
import java.util.Comparator;
import java.util.List;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

/**
 * Simple utility that mirrors a lightweight analytics bridge.
 * Reads community activity JSON (same structure as data/activity_feed.json)
 * and prints a ranked scoreboard that could be pushed to a websocket.
 */
public final class RealtimeBridge {
  private static final Pattern ENTRY_PATTERN = Pattern.compile("\\{([^}]+)\\}");
  private static final Pattern STRING_FIELD = Pattern.compile("\"(community|window_start)\"\\s*:\\s*\"([^\"]*)\"");
  private static final Pattern NUMBER_FIELD = Pattern.compile("\"(posts|comments)\"\\s*:\\s*(\\d+)");

  private RealtimeBridge() {}

  public static void main(String[] args) throws IOException {
    if (args.length == 0) {
      System.err.println("Usage: java RealtimeBridge <path-to-json>");
      System.exit(1);
    }

    Path file = Path.of(args[0]);
    if (!Files.exists(file)) {
      System.err.println("File not found: " + file);
      System.exit(1);
    }

    String json = Files.readString(file, StandardCharsets.UTF_8);
    List<Activity> activities = parse(json);
    activities.sort(Comparator.comparingInt(Activity::score).reversed());

    System.out.println("=== Community Pulse ===");
    for (Activity activity : activities) {
      System.out.printf(
        "r/%s -> posts: %d, comments: %d, score: %d, window: %s%n",
        activity.community,
        activity.posts,
        activity.comments,
        activity.score(),
        activity.windowStart
      );
    }
  }

  private static List<Activity> parse(String json) {
    List<Activity> activities = new ArrayList<>();
    Matcher entryMatcher = ENTRY_PATTERN.matcher(json);
    while (entryMatcher.find()) {
      String entry = entryMatcher.group(1);
      Activity activity = new Activity();
      Matcher stringMatcher = STRING_FIELD.matcher(entry);
      while (stringMatcher.find()) {
        String field = stringMatcher.group(1);
        String value = stringMatcher.group(2);
        if ("community".equals(field)) {
          activity.community = value;
        } else if ("window_start".equals(field)) {
          activity.windowStart = value;
        }
      }
      Matcher numberMatcher = NUMBER_FIELD.matcher(entry);
      while (numberMatcher.find()) {
        String field = numberMatcher.group(1);
        int value = Integer.parseInt(numberMatcher.group(2));
        if ("posts".equals(field)) {
          activity.posts = value;
        } else if ("comments".equals(field)) {
          activity.comments = value;
        }
      }
      if (activity.community != null) {
        activities.add(activity);
      }
    }
    return activities;
  }

  private static final class Activity {
    String community;
    int posts;
    int comments;
    String windowStart = "";

    int score() {
      return posts * 2 + comments;
    }
  }
}
