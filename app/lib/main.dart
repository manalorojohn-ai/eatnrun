import 'package:flutter/material.dart';
import 'package:animate_do/animate_do.dart';

void main() {
  runApp(const EatNRunApp());
}

class EatNRunApp extends StatelessWidget {
  const EatNRunApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Eat&Run',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(seedColor: const Color(0xFF00b09b)),
        useMaterial3: true,
        fontFamily: 'Poppins',
      ),
      home: const MainScreen(),
    );
  }
}

class MainScreen extends StatefulWidget {
  const MainScreen({super.key});

  @override
  State<MainScreen> createState() => _MainScreenState();
}

class _MainScreenState extends State<MainScreen> {
  int _currentIndex = 0;
  final List<Widget> _screens = [
    const HomeScreen(),
    const MenuScreen(),
    const AboutScreen(),
  ];

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFf8f9fa),
      body: _screens[_currentIndex],
      bottomNavigationBar: BottomNavigationBar(
        currentIndex: _currentIndex,
        onTap: (index) {
          setState(() {
            _currentIndex = index;
          });
        },
        selectedItemColor: const Color(0xFF00b09b),
        unselectedItemColor: const Color(0xFF636e72),
        items: const [
          BottomNavigationBarItem(
            icon: Icon(Icons.home),
            label: 'Home',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.restaurant_menu),
            label: 'Menu',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.info),
            label: 'About',
          ),
        ],
      ),
    );
  }
}

class HomeScreen extends StatelessWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      child: Column(
        children: [
          // Navbar
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 15),
            decoration: BoxDecoration(
              color: Colors.white,
              boxShadow: [
                BoxShadow(
                  color: Colors.grey.withOpacity(0.1),
                  spreadRadius: 2,
                  blurRadius: 10,
                ),
              ],
            ),
            child: Row(
              children: [
                Image.asset(
                  'assets/images/logo.png',
                  height: 40,
                  width: 40,
                  errorBuilder: (context, error, stackTrace) {
                    return const Icon(Icons.restaurant, color: Color(0xFF00b09b), size: 32);
                  },
                ),
                const SizedBox(width: 10),
                const Text(
                  'Eat&Run',
                  style: TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF2d3436),
                  ),
                ),
                const Spacer(),
                Row(
                  children: [
                    IconButton(
                      icon: const Icon(Icons.shopping_bag_outlined, color: Color(0xFF2d3436)),
                      onPressed: () {},
                    ),
                    const SizedBox(width: 10),
                    IconButton(
                      icon: const Icon(Icons.person_outline, color: Color(0xFF2d3436)),
                      onPressed: () {},
                    ),
                  ],
                ),
              ],
            ),
          ),

          // Hero Section
          FadeInUp(
            duration: const Duration(milliseconds: 600),
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 60),
              decoration: const BoxDecoration(
                gradient: LinearGradient(
                  colors: [
                    Color(0xFFf8f9fa),
                    Color(0xFFe6f7f5),
                  ],
                  begin: Alignment.topCenter,
                  end: Alignment.bottomCenter,
                ),
              ),
              child: Column(
                children: [
                  const Text(
                    'Welcome to Eat&Run!',
                    style: TextStyle(
                      fontSize: 32,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFF2d3436),
                    ),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 20),
                  const Text(
                    'Your favorite local restaurants delivered to your doorstep. Fast, fresh, and convenient food delivery service.',
                    style: TextStyle(
                      fontSize: 16,
                      color: Color(0xFF636e72),
                    ),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 30),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      ElevatedButton(
                        onPressed: () {
                          final mainScreen = context.findAncestorStateOfType<_MainScreenState>();
                          mainScreen?.setState(() {
                            mainScreen._currentIndex = 1;
                          });
                        },
                        style: ElevatedButton.styleFrom(
                          backgroundColor: const Color(0xFF00b09b),
                          foregroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(horizontal: 30, vertical: 15),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(12),
                          ),
                          elevation: 2,
                        ),
                        child: const Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Icon(Icons.restaurant),
                            SizedBox(width: 8),
                            Text(
                              'Browse Menu',
                              style: TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(width: 15),
                      OutlinedButton(
                        onPressed: () {
                          final mainScreen = context.findAncestorStateOfType<_MainScreenState>();
                          mainScreen?.setState(() {
                            mainScreen._currentIndex = 2;
                          });
                        },
                        style: OutlinedButton.styleFrom(
                          foregroundColor: const Color(0xFF00b09b),
                          side: const BorderSide(color: Color(0xFF00b09b)),
                          padding: const EdgeInsets.symmetric(horizontal: 30, vertical: 15),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(12),
                          ),
                        ),
                        child: const Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Icon(Icons.info),
                            SizedBox(width: 8),
                            Text(
                              'Learn More',
                              style: TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),

          // Featured Menu Section
          Container(
            padding: const EdgeInsets.all(20),
            color: Colors.white,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text(
                      'Featured Menu',
                      style: TextStyle(
                        fontSize: 24,
                        fontWeight: FontWeight.bold,
                        color: Color(0xFF2d3436),
                      ),
                    ),
                    TextButton(
                      onPressed: () {
                        final mainScreen = context.findAncestorStateOfType<_MainScreenState>();
                        mainScreen?.setState(() {
                          mainScreen._currentIndex = 1;
                        });
                      },
                      child: const Text(
                        'View All',
                        style: TextStyle(
                          color: Color(0xFF00b09b),
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 20),
                GridView.builder(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                    crossAxisCount: 2,
                    childAspectRatio: 0.9,
                    crossAxisSpacing: 15,
                    mainAxisSpacing: 15,
                  ),
                  itemCount: 4,
                  itemBuilder: (context, index) {
                    final List<String> items = [
                      'Plain Burger',
                      'Bicol Express',
                      'Halo-Halo',
                      'Mango Juice',
                    ];
                    final List<String> images = [
                      'assets/images/menu/Burgers/plain-burger.jpg',
                      'assets/images/menu/Rice Meals/bicol-express.jpg',
                      'assets/images/menu/Desserts/halo-halo.jpg',
                      'assets/images/menu/Beverages/mango-juice.jpg',
                    ];

                    return FadeInUp(
                      duration: Duration(milliseconds: 600 + index * 100),
                      child: GestureDetector(
                        onTap: () {
                          final mainScreen = context.findAncestorStateOfType<_MainScreenState>();
                          mainScreen?.setState(() {
                            mainScreen._currentIndex = 1;
                          });
                        },
                        child: Container(
                          decoration: BoxDecoration(
                            borderRadius: BorderRadius.circular(16),
                            color: Colors.white,
                            boxShadow: [
                              BoxShadow(
                                color: Colors.grey.withOpacity(0.08),
                                spreadRadius: 2,
                                blurRadius: 10,
                              ),
                            ],
                          ),
                          child: Column(
                            children: [
                              Expanded(
                                child: ClipRRect(
                                  borderRadius: const BorderRadius.vertical(top: Radius.circular(16)),
                                  child: Image.asset(
                                    images[index],
                                    width: double.infinity,
                                    fit: BoxFit.cover,
                                  ),
                                ),
                              ),
                              Padding(
                                padding: const EdgeInsets.all(12),
                                child: Text(
                                  items[index],
                                  style: const TextStyle(
                                    fontSize: 16,
                                    fontWeight: FontWeight.bold,
                                    color: Color(0xFF2d3436),
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    );
                  },
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class MenuScreen extends StatefulWidget {
  const MenuScreen({super.key});

  @override
  State<MenuScreen> createState() => _MenuScreenState();
}

class _MenuScreenState extends State<MenuScreen> {
  final List<Map<String, dynamic>> categories = [
    {'name': 'All', 'icon': Icons.restaurant, 'active': true},
    {'name': 'Burgers', 'icon': Icons.fastfood, 'active': false},
    {'name': 'Rice Meals', 'icon': Icons.rice_bowl, 'active': false},
    {'name': 'Beverages', 'icon': Icons.local_drink, 'active': false},
    {'name': 'Desserts', 'icon': Icons.icecream, 'active': false},
  ];

  final List<Map<String, dynamic>> menuItems = [
    {
      'id': 1,
      'name': 'Plain Burger',
      'description': 'Classic beef burger with fresh vegetables',
      'price': 95.00,
      'category': 'Burgers',
      'rating': 4.9,
      'time': '25-35 min',
      'image': 'assets/images/menu/Burgers/plain-burger.jpg',
    },
    {
      'id': 2,
      'name': 'Cheese Burger',
      'description': 'Juicy burger with melted cheddar cheese',
      'price': 120.00,
      'category': 'Burgers',
      'rating': 4.9,
      'time': '25-35 min',
      'image': 'assets/images/menu/Burgers/cheese-burger.jpg',
    },
    {
      'id': 3,
      'name': 'Adobo with Rice',
      'description': 'Classic Filipino adobo with steamed rice',
      'price': 150.00,
      'category': 'Rice Meals',
      'rating': 4.9,
      'time': '25-35 min',
      'image': 'assets/images/menu/Rice Meals/adobo.jpg',
    },
    {
      'id': 4,
      'name': 'Coke',
      'description': 'Refreshing ice-cold Coca-Cola',
      'price': 45.00,
      'category': 'Beverages',
      'rating': 4.9,
      'time': '25-35 min',
      'image': 'assets/images/menu/Beverages/coke.jpg',
    },
    {
      'id': 5,
      'name': 'Mango Juice',
      'description': 'Fresh Philippine mango juice',
      'price': 55.00,
      'category': 'Beverages',
      'rating': 4.9,
      'time': '25-35 min',
      'image': 'assets/images/menu/Beverages/mango-juice.jpg',
    },
    {
      'id': 6,
      'name': 'Halo-Halo',
      'description': 'Filipino shaved ice dessert',
      'price': 85.00,
      'category': 'Desserts',
      'rating': 4.9,
      'time': '25-35 min',
      'image': 'assets/images/menu/Desserts/halo-halo.jpg',
    },
    {
      'id': 7,
      'name': 'Leche Flan',
      'description': 'Classic caramel custard',
      'price': 60.00,
      'category': 'Desserts',
      'rating': 4.9,
      'time': '25-35 min',
      'image': 'assets/images/menu/Desserts/leche-flan.jpg',
    },
  ];

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      child: Column(
        children: [
          // Navbar
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 15),
            decoration: BoxDecoration(
              color: Colors.white,
              boxShadow: [
                BoxShadow(
                  color: Colors.grey.withOpacity(0.1),
                  spreadRadius: 2,
                  blurRadius: 10,
                ),
              ],
            ),
            child: Row(
              children: [
                Image.asset(
                  'assets/images/logo.png',
                  height: 40,
                  width: 40,
                  errorBuilder: (context, error, stackTrace) {
                    return const Icon(Icons.restaurant, color: Color(0xFF00b09b), size: 32);
                  },
                ),
                const SizedBox(width: 10),
                const Text(
                  'Eat&Run',
                  style: TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF2d3436),
                  ),
                ),
                const Spacer(),
                IconButton(
                  icon: const Icon(Icons.shopping_bag_outlined, color: Color(0xFF2d3436)),
                  onPressed: () {},
                ),
              ],
            ),
          ),

          // Hero Section
          FadeInUp(
            duration: const Duration(milliseconds: 600),
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 40),
              decoration: const BoxDecoration(
                gradient: LinearGradient(
                  colors: [
                    Color(0xFFf8f9fa),
                    Color(0xFFe6f7f5),
                  ],
                  begin: Alignment.topCenter,
                  end: Alignment.bottomCenter,
                ),
              ),
              child: Column(
                children: [
                  const Text(
                    'Our',
                    style: TextStyle(
                      fontSize: 40,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFF2d3436),
                    ),
                  ),
                  ShaderMask(
                    shaderCallback: (bounds) => const LinearGradient(
                      colors: [
                        Color(0xFF00b09b),
                        Color(0xFF96c93d),
                      ],
                    ).createShader(bounds),
                    child: const Text(
                      'Menu',
                      style: TextStyle(
                        fontSize: 40,
                        fontWeight: FontWeight.bold,
                        color: Colors.white,
                      ),
                    ),
                  ),
                  const SizedBox(height: 30),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(50),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.grey.withOpacity(0.08),
                          spreadRadius: 2,
                          blurRadius: 10,
                        ),
                      ],
                    ),
                    child: Row(
                      children: [
                        const Icon(Icons.search, color: Color(0xFF636e72)),
                        const SizedBox(width: 15),
                        Expanded(
                          child: TextField(
                            decoration: const InputDecoration(
                              hintText: 'What are you craving today?',
                              hintStyle: TextStyle(color: Color(0xFF636e72)),
                              border: InputBorder.none,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),

          // Categories
          FadeInUp(
            duration: const Duration(milliseconds: 700),
            child: Padding(
              padding: const EdgeInsets.only(top: 20),
              child: SingleChildScrollView(
                scrollDirection: Axis.horizontal,
                padding: const EdgeInsets.symmetric(horizontal: 20),
                child: Row(
                  children: categories.asMap().entries.map((entry) {
                    final index = entry.key;
                    final category = entry.value;
                    return Padding(
                      padding: EdgeInsets.only(left: index == 0 ? 0 : 10),
                      child: GestureDetector(
                        onTap: () {
                          setState(() {
                            for (var cat in categories) {
                              cat['active'] = cat == category;
                            }
                          });
                        },
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
                          decoration: BoxDecoration(
                            gradient: category['active']
                                ? const LinearGradient(
                                    colors: [
                                      Color(0xFF00b09b),
                                      Color(0xFF96c93d),
                                    ],
                                  )
                                : null,
                            color: category['active'] ? null : Colors.white,
                            borderRadius: BorderRadius.circular(50),
                            border: category['active']
                                ? null
                                : Border.all(color: const Color(0xFFeee)),
                            boxShadow: category['active']
                                ? [
                                    BoxShadow(
                                      color: const Color(0xFF00b09b).withOpacity(0.3),
                                      blurRadius: 15,
                                      offset: const Offset(0, 5),
                                    ),
                                  ]
                                : null,
                          ),
                          child: Row(
                            children: [
                              Icon(
                                category['icon'],
                                color: category['active'] ? Colors.white : const Color(0xFF636e72),
                                size: 18,
                              ),
                              const SizedBox(width: 8),
                              Text(
                                category['name'],
                                style: TextStyle(
                                  color: category['active'] ? Colors.white : const Color(0xFF636e72),
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    );
                  }).toList(),
                ),
              ),
            ),
          ),

          // Menu Grid
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 30),
            child: GridView.builder(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: 2,
                childAspectRatio: 0.7,
                crossAxisSpacing: 20,
                mainAxisSpacing: 25,
              ),
              itemCount: menuItems.length,
              itemBuilder: (context, index) {
                final item = menuItems[index];
                return FadeInUp(
                  duration: Duration(milliseconds: 800 + index * 100),
                  child: _buildMenuItem(item),
                );
              },
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildMenuItem(Map<String, dynamic> item) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: Colors.grey.withOpacity(0.08),
            spreadRadius: 2,
            blurRadius: 10,
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Stack(
            children: [
              ClipRRect(
                borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
                child: Image.asset(
                  item['image'],
                  height: 150,
                  width: double.infinity,
                  fit: BoxFit.cover,
                ),
              ),
              Positioned(
                top: 12,
                right: 12,
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 5),
                  decoration: BoxDecoration(
                    color: Colors.white.withOpacity(0.95),
                    borderRadius: BorderRadius.circular(50),
                  ),
                  child: Text(
                    item['category'],
                    style: const TextStyle(
                      fontSize: 10,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFF2d3436),
                    ),
                  ),
                ),
              ),
            ],
          ),
          Padding(
            padding: const EdgeInsets.all(15),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Row(
                      children: [
                        const Icon(Icons.star, color: Color(0xFFFFD700), size: 16),
                        const SizedBox(width: 4),
                        Text(
                          item['rating'].toString(),
                          style: const TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w600,
                            color: Color(0xFF2d3436),
                          ),
                        ),
                      ],
                    ),
                    Row(
                      children: [
                        const Icon(Icons.access_time, color: Color(0xFF636e72), size: 14),
                        const SizedBox(width: 4),
                        Text(
                          item['time'],
                          style: const TextStyle(
                            fontSize: 11,
                            color: Color(0xFF636e72),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                Text(
                  item['name'],
                  style: const TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF2d3436),
                  ),
                ),
                const SizedBox(height: 5),
                Text(
                  item['description'],
                  style: const TextStyle(
                    fontSize: 12,
                    color: Color(0xFF636e72),
                  ),
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
                const SizedBox(height: 12),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: [
                        const Text(
                          '₱',
                          style: TextStyle(
                            fontSize: 14,
                            fontWeight: FontWeight.bold,
                            color: Color(0xFF2d3436),
                          ),
                        ),
                        Text(
                          item['price'].toStringAsFixed(2),
                          style: const TextStyle(
                            fontSize: 20,
                            fontWeight: FontWeight.bold,
                            color: Color(0xFF2d3436),
                          ),
                        ),
                      ],
                    ),
                    Container(
                      decoration: BoxDecoration(
                        color: const Color(0xFF2d3436),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      padding: const EdgeInsets.symmetric(horizontal: 15, vertical: 8),
                      child: const Icon(
                        Icons.shopping_bag_outlined,
                        color: Colors.white,
                        size: 18,
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class AboutScreen extends StatelessWidget {
  const AboutScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      child: Column(
        children: [
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 15),
            decoration: BoxDecoration(
              color: Colors.white,
              boxShadow: [
                BoxShadow(
                  color: Colors.grey.withOpacity(0.1),
                  spreadRadius: 2,
                  blurRadius: 10,
                ),
              ],
            ),
            child: Row(
              children: [
                Image.asset(
                  'assets/images/logo.png',
                  height: 40,
                  width: 40,
                  errorBuilder: (context, error, stackTrace) {
                    return const Icon(Icons.restaurant, color: Color(0xFF00b09b), size: 32);
                  },
                ),
                const SizedBox(width: 10),
                const Text(
                  'Eat&Run',
                  style: TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF2d3436),
                  ),
                ),
              ],
            ),
          ),
          const Padding(
            padding: EdgeInsets.all(40),
            child: Column(
              children: [
                Icon(Icons.info_outline, size: 80, color: Color(0xFF00b09b)),
                SizedBox(height: 20),
                Text(
                  'About Eat&Run',
                  style: TextStyle(
                    fontSize: 28,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF2d3436),
                  ),
                ),
                SizedBox(height: 20),
                Text(
                  'Your favorite local restaurants delivered to your doorstep. Fast, fresh, and convenient food delivery service.',
                  style: TextStyle(
                    fontSize: 16,
                    color: Color(0xFF636e72),
                  ),
                  textAlign: TextAlign.center,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
