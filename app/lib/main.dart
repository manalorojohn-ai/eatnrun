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
      home: const HomeScreen(),
    );
  }
}

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
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
      'image': 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=400&h=400&fit=crop',
    },
    {
      'id': 2,
      'name': 'Cheese Burger',
      'description': 'Juicy burger with melted cheddar cheese',
      'price': 120.00,
      'category': 'Burgers',
      'rating': 4.9,
      'time': '25-35 min',
      'image': 'https://images.unsplash.com/photo-1551782450-17144efb9c50?w=400&h=400&fit=crop',
    },
    {
      'id': 3,
      'name': 'Adobo with Rice',
      'description': 'Classic Filipino adobo with steamed rice',
      'price': 150.00,
      'category': 'Rice Meals',
      'rating': 4.9,
      'time': '25-35 min',
      'image': 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=400&h=400&fit=crop',
    },
    {
      'id': 4,
      'name': 'Coke',
      'description': 'Refreshing ice-cold Coca-Cola',
      'price': 45.00,
      'category': 'Beverages',
      'rating': 4.9,
      'time': '25-35 min',
      'image': 'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?w=400&h=400&fit=crop',
    },
    {
      'id': 5,
      'name': 'Mango Juice',
      'description': 'Fresh Philippine mango juice',
      'price': 55.00,
      'category': 'Beverages',
      'rating': 4.9,
      'time': '25-35 min',
      'image': 'https://images.unsplash.com/photo-1556910103-1c02745aae4d?w=400&h=400&fit=crop',
    },
    {
      'id': 6,
      'name': 'Halo-Halo',
      'description': 'Filipino shaved ice dessert',
      'price': 85.00,
      'category': 'Desserts',
      'rating': 4.9,
      'time': '25-35 min',
      'image': 'https://images.unsplash.com/photo-1523475405774-0a8d87293482?w=400&h=400&fit=crop',
    },
    {
      'id': 7,
      'name': 'Leche Flan',
      'description': 'Classic caramel custard',
      'price': 60.00,
      'category': 'Desserts',
      'rating': 4.9,
      'time': '25-35 min',
      'image': 'https://images.unsplash.com/photo-1571771894821-ce9b6c11b08e?w=400&h=400&fit=crop',
    },
  ];

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFf8f9fa),
      body: SafeArea(
        child: SingleChildScrollView(
          child: Column(
            children: [
              _buildHeader(),
              _buildHero(),
              _buildCategories(),
              _buildMenuGrid(),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildHeader() {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 15),
      child: FadeInDown(
        duration: const Duration(milliseconds: 500),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(12),
                boxShadow: [
                  BoxShadow(
                    color: Colors.grey.withOpacity(0.1),
                    spreadRadius: 2,
                    blurRadius: 10,
                  ),
                ],
              ),
              child: const Icon(Icons.menu, color: Color(0xFF00b09b)),
            ),
            const Spacer(),
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(12),
                boxShadow: [
                  BoxShadow(
                    color: Colors.grey.withOpacity(0.1),
                    spreadRadius: 2,
                    blurRadius: 10,
                  ),
                ],
              ),
              child: Stack(
                children: [
                  const Icon(Icons.shopping_bag_outlined, color: Color(0xFF2d3436)),
                  Positioned(
                    right: 0,
                    top: 0,
                    child: Container(
                      padding: const EdgeInsets.all(4),
                      decoration: const BoxDecoration(
                        color: Color(0xFF00b09b),
                        shape: BoxShape.circle,
                      ),
                      child: const Text(
                        '0',
                        style: TextStyle(
                          color: Colors.white,
                          fontSize: 10,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildHero() {
    return FadeInUp(
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
                    blurRadius: 20,
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
    );
  }

  Widget _buildCategories() {
    return FadeInUp(
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
                      border: category['active'] ? null : Border.all(color: const Color(0xFFeee)),
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
    );
  }

  Widget _buildMenuGrid() {
    return Padding(
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
            blurRadius: 15,
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
                child: Image.network(
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
